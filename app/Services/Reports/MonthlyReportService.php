<?php

namespace App\Services\Reports;

use App\Models\Expense;
use App\Models\TransactionDetail;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MonthlyReportService
{
    public function getCurrentMonthSummary(?Carbon $month = null): array
    {
        $targetMonth = $month ?? now();
        $year = (int) $targetMonth->year;
        $monthNumber = (int) $targetMonth->month;

        $monthRevenue = (float) Transaction::query()
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $monthNumber)
            ->sum('total_amount');

        $monthExpenses = (float) Expense::query()
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $monthNumber)
            ->sum('amount');

        return [
            'month_revenue' => $monthRevenue,
            'month_expenses' => $monthExpenses,
            'month_profit_estimate' => $monthRevenue - $monthExpenses,
        ];
    }

    public function getMonthlySummary(?Carbon $month = null): array
    {
        return $this->getCurrentMonthSummary($month);
    }

    public function getMonthlyRevenueReport(int $year): Collection
    {
        return TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereYear('transactions.transaction_date', $year)
            ->selectRaw('
                MONTH(transactions.transaction_date) as month_number,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as service_revenue,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as product_revenue,
                COALESCE(SUM(CASE WHEN transaction_items.item_type IN (?, ?) THEN transaction_items.subtotal ELSE 0 END), 0) as total_revenue
            ', ['service', 'product', 'service', 'product'])
            ->groupByRaw('MONTH(transactions.transaction_date)')
            ->orderBy('month_number')
            ->get()
            ->map(function ($row) {
                return [
                    'month_number' => (int) $row->month_number,
                    'service_revenue' => (float) $row->service_revenue,
                    'product_revenue' => (float) $row->product_revenue,
                    'total_revenue' => (float) $row->total_revenue,
                ];
            });
    }
}
