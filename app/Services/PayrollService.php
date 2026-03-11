<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollService
{
    private const PAYROLL_STATUS_OPEN = 'open';
    private const PAYROLL_STATUS_CLOSED = 'closed';
    private const EMPLOYEE_STATUS_TETAP = 'tetap';
    private const EXISTING_OPEN_MESSAGE = 'Masih ada payroll open yang belum ditutup.';
    private const INVALID_CLOSE_MESSAGE = 'Payroll ini sudah ditutup dan tidak dapat diproses ulang.';
    private const EMPTY_PAYROLL_MESSAGE = 'Tidak ada transaksi dalam periode payroll.';

    public function openPayroll(string $startDate, string $endDate): PayrollPeriod
    {
        return DB::transaction(function () use ($startDate, $endDate): PayrollPeriod {
            $this->assertNoOpenPayrollExists();
            $startDateParsed = Carbon::parse($startDate)->toDateString();
            $endDateParsed = Carbon::parse($endDate)->toDateString();

            return PayrollPeriod::query()->create([
                'start_date' => $startDateParsed,
                'end_date' => $endDateParsed,
                'status' => self::PAYROLL_STATUS_OPEN,
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

            [$startDateTime, $endDateTime, $effectiveEndDate] = $this->resolvePayrollDateRange($payrollPeriod);

            $transactionIds = Transaction::query()
                ->whereNull('payroll_id')
                ->where('transaction_date', '>=', $startDateTime)
                ->where('transaction_date', '<=', $endDateTime)
                ->lockForUpdate()
                ->pluck('id');

            if (config('app.debug')) {
                Log::debug('Payroll closing debug', [
                    'payroll_id' => $payrollPeriod->id,
                    'start_datetime' => $startDateTime->toDateTimeString(),
                    'end_datetime' => $endDateTime->toDateTimeString(),
                    'transaction_count' => $transactionIds->count(),
                    'transaction_ids_sample' => $transactionIds->take(20)->values()->all(),
                ]);
            }

            if ($transactionIds->isEmpty()) {
                throw new DomainException(self::EMPTY_PAYROLL_MESSAGE);
            }

            $resultRows = TransactionDetail::query()
                ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->join('employees', 'employees.id', '=', 'transactions.employee_id')
                ->where('employees.status', self::EMPLOYEE_STATUS_TETAP)
                ->whereIn('transactions.id', $transactionIds)
                ->selectRaw('
                    transactions.employee_id as employee_id,
                    COUNT(DISTINCT transactions.id) as total_transactions,
                    SUM(CASE WHEN transaction_items.item_type = \'service\' THEN transaction_items.qty ELSE 0 END) as total_services,
                    SUM(CASE WHEN transaction_items.item_type = \'product\' THEN transaction_items.qty ELSE 0 END) as total_products,
                    SUM(transaction_items.commission_amount) as total_commission
                ')
                ->groupBy('transactions.employee_id')
                ->get();

            if ($resultRows->isNotEmpty()) {
                $now = now();

                $insertPayload = $resultRows->map(fn ($row) => [
                    'payroll_period_id' => $payrollPeriod->id,
                    'employee_id' => (int) $row->employee_id,
                    'total_transactions' => (int) $row->total_transactions,
                    'total_services' => (int) $row->total_services,
                    'total_products' => (int) $row->total_products,
                    'total_commission' => (float) $row->total_commission,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('payroll_results')->insert($insertPayload);
            }

            if ($transactionIds->isNotEmpty()) {
                Transaction::query()
                    ->whereIn('id', $transactionIds)
                    ->update([
                        'payroll_id' => $payrollPeriod->id,
                    ]);
            }

            $payrollPeriod->update([
                'end_date' => $payrollPeriod->end_date ?? $effectiveEndDate->toDateString(),
                'closed_at' => now(),
                'status' => self::PAYROLL_STATUS_CLOSED,
            ]);

            return $payrollPeriod->fresh();
        });
    }

    public function countPendingTransactionsForPeriod(PayrollPeriod $payrollPeriod): int
    {
        [$startDateTime, $endDateTime] = $this->resolvePayrollDateRange($payrollPeriod);

        return Transaction::query()
            ->whereNull('payroll_id')
            ->where('transaction_date', '>=', $startDateTime)
            ->where('transaction_date', '<=', $endDateTime)
            ->count();
    }

    private function assertPayrollCanBeClosed(PayrollPeriod $payrollPeriod): void
    {
        if ($payrollPeriod->status !== self::PAYROLL_STATUS_OPEN) {
            throw new DomainException(self::INVALID_CLOSE_MESSAGE);
        }

        if ($payrollPeriod->closed_at !== null) {
            throw new DomainException(self::INVALID_CLOSE_MESSAGE);
        }

        if ($payrollPeriod->payrollResults()->exists()) {
            throw new DomainException(self::INVALID_CLOSE_MESSAGE);
        }
    }

    private function assertNoOpenPayrollExists(): void
    {
        $hasOpenPayroll = PayrollPeriod::query()
            ->where('status', self::PAYROLL_STATUS_OPEN)
            ->exists();

        if ($hasOpenPayroll) {
            throw new DomainException(self::EXISTING_OPEN_MESSAGE);
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: Carbon}
     */
    private function resolvePayrollDateRange(PayrollPeriod $payrollPeriod): array
    {
        $startDateTime = Carbon::parse($payrollPeriod->start_date)->startOfDay();
        $effectiveEndDate = $payrollPeriod->end_date !== null
            ? Carbon::parse($payrollPeriod->end_date)
            : Carbon::today();
        $endDateTime = $effectiveEndDate->copy()->endOfDay();

        return [$startDateTime, $endDateTime, $effectiveEndDate];
    }
}
