<?php

namespace App\Services\Reports;

use App\Models\Expense;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

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
}
