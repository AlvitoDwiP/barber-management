<?php

namespace App\Services\Reports;

use App\Models\TransactionDetail;
use Illuminate\Support\Carbon;

class EmployeePerformanceReportService
{
    public function getTopEmployeeOfMonth(?Carbon $month = null): ?array
    {
        $targetMonth = $month ?? now();
        $year = (int) $targetMonth->year;
        $monthNumber = (int) $targetMonth->month;

        $topEmployee = TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('employees', 'employees.id', '=', 'transactions.employee_id')
            ->where('transaction_items.item_type', 'service')
            ->whereYear('transactions.transaction_date', $year)
            ->whereMonth('transactions.transaction_date', $monthNumber)
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
}
