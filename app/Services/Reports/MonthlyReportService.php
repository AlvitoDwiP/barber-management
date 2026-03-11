<?php

namespace App\Services\Reports;

use App\Models\Expense;
use App\Models\TransactionDetail;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonthlyReportService
{
    public function getCurrentMonthSummary(?Carbon $month = null): array
    {
        $targetMonth = $month ?? now();
        $year = (int) $targetMonth->year;
        $monthNumber = (int) $targetMonth->month;
        $startDate = Carbon::create($year, $monthNumber, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $monthNumber, 1)->endOfMonth()->toDateString();

        $monthRevenue = (float) Transaction::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('total_amount');

        $monthExpenses = (float) Expense::query()
            ->whereBetween('expense_date', [$startDate, $endDate])
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
        $startDate = Carbon::create($year, 1, 1)->startOfYear()->toDateString();
        $endDate = Carbon::create($year, 1, 1)->endOfYear()->toDateString();
        ['year' => $yearExpression, 'month' => $monthExpression] = $this->getYearMonthExpressions('transactions.transaction_date');

        return TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->selectRaw('
                '.$monthExpression.' as month_number,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as service_revenue,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as product_revenue,
                COALESCE(SUM(CASE WHEN transaction_items.item_type IN (?, ?) THEN transaction_items.subtotal ELSE 0 END), 0) as total_revenue
            ', ['service', 'product', 'service', 'product'])
            ->groupByRaw($yearExpression.', '.$monthExpression)
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

    private function getYearMonthExpressions(string $column): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return [
                'year' => "CAST(strftime('%Y', {$column}) AS INTEGER)",
                'month' => "CAST(strftime('%m', {$column}) AS INTEGER)",
            ];
        }

        if ($driver === 'pgsql') {
            return [
                'year' => "EXTRACT(YEAR FROM {$column})",
                'month' => "EXTRACT(MONTH FROM {$column})",
            ];
        }

        return [
            'year' => "YEAR({$column})",
            'month' => "MONTH({$column})",
        ];
    }
}
