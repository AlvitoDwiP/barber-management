<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    private const PAYROLL_STATUS_OPEN = 'open';
    private const PAYROLL_STATUS_CLOSED = 'closed';
    private const EMPLOYEE_STATUS_TETAP = 'tetap';
    private const EXISTING_OPEN_MESSAGE = 'Masih ada payroll open yang belum ditutup.';
    private const INVALID_CLOSE_MESSAGE = 'Payroll ini sudah ditutup dan tidak dapat diproses ulang.';
    private const EMPTY_PAYROLL_MESSAGE = 'Tidak ada transaksi dalam periode payroll.';

    public function openPayroll(): PayrollPeriod
    {
        return DB::transaction(function (): PayrollPeriod {
            $this->assertNoOpenPayrollExists();
            $startDate = Carbon::today();

            return PayrollPeriod::query()->create([
                'start_date' => $startDate,
                'end_date' => null,
                'status' => self::PAYROLL_STATUS_OPEN,
                'closed_at' => null,
            ]);
        });
    }

    public function closePayroll(PayrollPeriod $payrollPeriod): PayrollPeriod
    {
        return DB::transaction(function () use ($payrollPeriod): PayrollPeriod {
            $payrollPeriod = PayrollPeriod::query()
                ->whereKey($payrollPeriod->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertPayrollCanBeClosed($payrollPeriod);

            $endDate = Carbon::today();
            $startDate = $payrollPeriod->start_date->toDateString();
            $endDateString = $endDate->toDateString();

            $transactionIds = Transaction::query()
                ->whereNull('payroll_id')
                ->whereBetween('transaction_date', [$startDate, $endDateString])
                ->lockForUpdate()
                ->pluck('id');

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
                'end_date' => $endDate,
                'closed_at' => now(),
                'status' => self::PAYROLL_STATUS_CLOSED,
            ]);

            return $payrollPeriod->fresh();
        });
    }

    private function assertPayrollCanBeClosed(PayrollPeriod $payrollPeriod): void
    {
        if ($payrollPeriod->status !== self::PAYROLL_STATUS_OPEN) {
            throw new DomainException(self::INVALID_CLOSE_MESSAGE);
        }

        if ($payrollPeriod->end_date !== null || $payrollPeriod->closed_at !== null) {
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
}
