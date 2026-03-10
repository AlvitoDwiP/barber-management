<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\TransactionDetail;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function openPayroll(): PayrollPeriod
    {
        return DB::transaction(function (): PayrollPeriod {
            $hasOpenPayroll = PayrollPeriod::query()
                ->where('status', 'open')
                ->exists();

            if ($hasOpenPayroll) {
                throw new DomainException('Masih ada payroll open yang belum ditutup.');
            }

            $latestClosedPayroll = PayrollPeriod::query()
                ->where('status', 'closed')
                ->whereNotNull('end_date')
                ->orderByDesc('end_date')
                ->orderByDesc('id')
                ->first();

            $startDate = $latestClosedPayroll !== null
                ? $latestClosedPayroll->end_date->copy()->addDay()
                : Carbon::today();

            return PayrollPeriod::query()->create([
                'start_date' => $startDate,
                'end_date' => null,
                'status' => 'open',
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

            if ($payrollPeriod->status !== 'open') {
                throw new DomainException('Payroll ini tidak dapat ditutup karena statusnya bukan open.');
            }

            if ($payrollPeriod->end_date !== null || $payrollPeriod->closed_at !== null) {
                throw new DomainException('Payroll ini sudah ditutup.');
            }

            if ($payrollPeriod->payrollResults()->exists()) {
                throw new DomainException('Payroll ini sudah memiliki hasil payroll.');
            }

            $endDate = Carbon::today();
            $startDate = $payrollPeriod->start_date->toDateString();
            $endDateString = $endDate->toDateString();

            $resultRows = TransactionDetail::query()
                ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
                ->join('employees', 'employees.id', '=', 'transactions.employee_id')
                ->where('employees.status', 'tetap')
                ->whereBetween('transactions.transaction_date', [$startDate, $endDateString])
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

            $payrollPeriod->update([
                'end_date' => $endDate,
                'closed_at' => now(),
                'status' => 'closed',
            ]);

            return $payrollPeriod->fresh();
        });
    }
}
