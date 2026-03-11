<?php

namespace App\Services\Reports;

use App\Models\TransactionDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EmployeePerformanceReportService
{
    public function getTopEmployeeOfMonth(?Carbon $month = null): ?array
    {
        $targetMonth = $month ?? now();
        $year = (int) $targetMonth->year;
        $monthNumber = (int) $targetMonth->month;
        $startDate = Carbon::create($year, $monthNumber, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $monthNumber, 1)->endOfMonth()->toDateString();

        $topEmployee = TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('employees', 'employees.id', '=', 'transactions.employee_id')
            ->where('transaction_items.item_type', 'service')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->groupBy('transactions.employee_id', 'employees.name')
            ->selectRaw('
                employees.name as employee_name,
                COUNT(transaction_items.id) as service_count
            ')
            ->orderByDesc('service_count')
            ->first();

        return [
            'employee_name' => $topEmployee?->employee_name,
            'service_count' => (int) ($topEmployee?->service_count ?? 0),
        ];
    }

    public function getEmployeePerformanceReport(int $month, int $year): Collection
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('employees', 'employees.id', '=', 'transactions.employee_id')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->groupBy('employees.id', 'employees.name')
            ->selectRaw('
                employees.name as employee_name,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN 1 ELSE 0 END), 0) as total_services,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.qty ELSE 0 END), 0) as total_products,
                COALESCE(SUM(transaction_items.commission_amount), 0) as total_commission
            ', ['service', 'product'])
            ->orderByDesc('total_commission')
            ->get()
            ->map(function ($row) {
                return [
                    'employee_name' => $row->employee_name,
                    'total_services' => (int) $row->total_services,
                    'total_products' => (int) $row->total_products,
                    'total_commission' => (float) $row->total_commission,
                ];
            });
    }
}
