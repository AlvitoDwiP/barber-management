<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollResult;
use App\Models\Transaction;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PayrollService
{
    private const EXISTING_OPEN_MESSAGE = 'Masih ada payroll open yang belum ditutup.';
    private const INVALID_CLOSE_MESSAGE = 'Payroll ini sudah ditutup dan tidak dapat diproses ulang.';
    private const EMPTY_PAYROLL_MESSAGE = 'Tidak ada transaksi dalam periode payroll.';
    private const EXISTING_RESULTS_MESSAGE = 'Payroll ini sudah memiliki snapshot hasil dan tidak dapat diproses ulang.';

    public function __construct(
        private readonly CommissionSummaryService $commissionSummaryService,
    ) {
    }

    public function openPayroll(string $startDate, string $endDate): PayrollPeriod
    {
        return DB::transaction(function () use ($startDate, $endDate): PayrollPeriod {
            $this->assertNoOpenPayrollExists();

            return PayrollPeriod::query()->create([
                'start_date' => Carbon::parse($startDate)->toDateString(),
                'end_date' => Carbon::parse($endDate)->toDateString(),
                'status' => PayrollPeriod::STATUS_OPEN,
                'closed_at' => null,
            ]);
        });
    }

    public function hasOverlapPeriod(string $startDate, string $endDate): bool
    {
        $startDateParsed = Carbon::parse($startDate)->toDateString();
        $endDateParsed = Carbon::parse($endDate)->toDateString();

        return PayrollPeriod::query()
            ->where(function ($query) use ($startDateParsed, $endDateParsed): void {
                $query
                    ->where('start_date', '<=', $endDateParsed)
                    ->where('end_date', '>=', $startDateParsed);
            })
            ->exists();
    }

    public function closePayroll(PayrollPeriod $payrollPeriod): PayrollPeriod
    {
        return DB::transaction(function () use ($payrollPeriod): PayrollPeriod {
            $payrollPeriod = PayrollPeriod::query()
                ->whereKey($payrollPeriod->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertPayrollCanBeClosed($payrollPeriod);

            [$startDate, $endDate, $effectiveEndDate] = $this->resolvePayrollDateRange($payrollPeriod);
            $transactionIds = $this->getPendingTransactionIdsForPeriod($startDate, $endDate, true);

            if (config('app.debug')) {
                Log::debug('Payroll closing debug', [
                    'payroll_period_id' => $payrollPeriod->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'transaction_count' => $transactionIds->count(),
                    'transaction_ids_sample' => $transactionIds->take(20)->values()->all(),
                ]);
            }

            if ($transactionIds->isEmpty()) {
                throw new DomainException(self::EMPTY_PAYROLL_MESSAGE);
            }

            $snapshotRows = $this->buildSnapshotRowsFromTransactionIds($transactionIds);

            $this->persistPayrollResults($payrollPeriod, $snapshotRows);
            $this->assignTransactionsToPayroll($payrollPeriod, $transactionIds);

            $payrollPeriod->update([
                'end_date' => $payrollPeriod->end_date ?? $effectiveEndDate->toDateString(),
                'closed_at' => now(),
                'status' => PayrollPeriod::STATUS_CLOSED,
            ]);

            return $payrollPeriod->fresh();
        });
    }

    public function countPendingTransactionsForPeriod(PayrollPeriod $payrollPeriod): int
    {
        [$startDate, $endDate] = $this->resolvePayrollDateRange($payrollPeriod);

        return $this->pendingTransactionsBaseQuery($startDate, $endDate)
            ->distinct('transactions.id')
            ->count('transactions.id');
    }

    public function getPayrollDisplayRows(PayrollPeriod $payrollPeriod): Collection
    {
        if ($payrollPeriod->status === PayrollPeriod::STATUS_CLOSED) {
            return $this->getClosedPayrollRows($payrollPeriod);
        }

        [$startDate, $endDate] = $this->resolvePayrollDateRange($payrollPeriod);

        return $this->buildSnapshotRowsFromTransactionIds(
            $this->getPendingTransactionIdsForPeriod($startDate, $endDate)
        )->sortBy(fn ($row) => mb_strtolower((string) $row->employee_name))
            ->values();
    }

    private function getClosedPayrollRows(PayrollPeriod $payrollPeriod): Collection
    {
        return $payrollPeriod->payrollResults()
            ->orderBy('employee_name')
            ->orderBy('employee_id')
            ->get([
                'id',
                'payroll_period_id',
                'employee_id',
                'employee_name',
                'total_transaction_count',
                'total_transactions',
                'total_services',
                'total_products',
                'total_service_amount',
                'total_service_commission',
                'total_product_commission',
                'total_commission',
            ])
            ->map(function (PayrollResult $result): object {
                return (object) [
                    'employee_id' => $result->employee_id,
                    'employee_name' => $result->display_employee_name,
                    'total_transaction_count' => (int) ($result->total_transaction_count ?: $result->total_transactions),
                    'total_services' => (int) $result->total_services,
                    'total_products' => (int) $result->total_products,
                    'total_service_amount' => (string) $result->total_service_amount,
                    'total_service_commission' => (string) $result->total_service_commission,
                    'total_product_commission' => (string) $result->total_product_commission,
                    'total_commission' => (string) $result->total_commission,
                ];
            })
            ->values();
    }

    private function getPendingTransactionIdsForPeriod(string $startDate, string $endDate, bool $lockForUpdate = false): Collection
    {
        $query = $this->pendingTransactionsBaseQuery($startDate, $endDate)
            ->select('transactions.id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->distinct()->pluck('transactions.id');
    }

    private function pendingTransactionsBaseQuery(string $startDate, string $endDate)
    {
        $query = Transaction::query()
            ->whereNull('payroll_id')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        if ($this->usesItemEmployeeSnapshots()) {
            return $query
                ->join('transaction_items', 'transaction_items.transaction_id', '=', 'transactions.id')
                ->where('transaction_items.employee_employment_type', Employee::EMPLOYMENT_TYPE_PERMANENT);
        }

        return $query
            ->join('employees', 'employees.id', '=', 'transactions.employee_id')
            ->where('employees.employment_type', Employee::EMPLOYMENT_TYPE_PERMANENT);
    }

    private function usesItemEmployeeSnapshots(): bool
    {
        return Schema::hasColumn('transaction_items', 'employee_employment_type');
    }

    private function buildSnapshotRowsFromTransactionIds(Collection $transactionIds): Collection
    {
        return $this->commissionSummaryService->getPayrollSnapshotRows($transactionIds);
    }

    private function persistPayrollResults(PayrollPeriod $payrollPeriod, Collection $snapshotRows): void
    {
        if ($payrollPeriod->payrollResults()->exists()) {
            throw new DomainException(self::EXISTING_RESULTS_MESSAGE);
        }

        if ($snapshotRows->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('payroll_results')->insert(
            $snapshotRows->map(fn (object $row) => [
                'payroll_period_id' => $payrollPeriod->id,
                'employee_id' => $row->employee_id,
                'employee_name' => $row->employee_name,
                'total_transactions' => $row->total_transaction_count,
                'total_transaction_count' => $row->total_transaction_count,
                'total_services' => $row->total_services,
                'total_products' => $row->total_products,
                'total_service_amount' => $row->total_service_amount,
                'total_service_commission' => $row->total_service_commission,
                'total_product_commission' => $row->total_product_commission,
                'total_commission' => $row->total_commission,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    private function assignTransactionsToPayroll(PayrollPeriod $payrollPeriod, Collection $transactionIds): void
    {
        if ($transactionIds->isEmpty()) {
            return;
        }

        Transaction::query()
            ->whereIn('id', $transactionIds)
            ->update([
                'payroll_id' => $payrollPeriod->id,
                'payroll_period_id' => $payrollPeriod->id,
            ]);
    }

    private function assertPayrollCanBeClosed(PayrollPeriod $payrollPeriod): void
    {
        if ($payrollPeriod->status !== PayrollPeriod::STATUS_OPEN) {
            throw new DomainException(self::INVALID_CLOSE_MESSAGE);
        }

        if ($payrollPeriod->closed_at !== null) {
            throw new DomainException(self::INVALID_CLOSE_MESSAGE);
        }

        if ($payrollPeriod->payrollResults()->exists()) {
            throw new DomainException(self::EXISTING_RESULTS_MESSAGE);
        }
    }

    private function assertNoOpenPayrollExists(): void
    {
        $hasOpenPayroll = PayrollPeriod::query()
            ->where('status', PayrollPeriod::STATUS_OPEN)
            ->exists();

        if ($hasOpenPayroll) {
            throw new DomainException(self::EXISTING_OPEN_MESSAGE);
        }
    }

    /**
     * @return array{0: string, 1: string, 2: Carbon}
     */
    private function resolvePayrollDateRange(PayrollPeriod $payrollPeriod): array
    {
        $startDate = Carbon::parse($payrollPeriod->start_date)->toDateString();
        $effectiveEndDate = $payrollPeriod->end_date !== null
            ? Carbon::parse($payrollPeriod->end_date)
            : Carbon::today();
        $endDate = $effectiveEndDate->toDateString();

        return [$startDate, $endDate, $effectiveEndDate];
    }
}
