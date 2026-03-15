<?php

namespace App\Services\Reports;

use App\Models\Expense;
use App\Models\TransactionItem;
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
        $transactionRows = $this->getMonthlyTransactionMetrics($year)->keyBy('month_number');
        $expenseRows = $this->getMonthlyExpenseMetrics($year)->keyBy('month_number');

        return collect(range(1, 12))
            ->map(function (int $monthNumber) use ($transactionRows, $expenseRows): array {
                $transactionRow = $transactionRows->get($monthNumber, []);
                $expenseRow = $expenseRows->get($monthNumber, []);

                $serviceRevenue = (float) ($transactionRow['service_revenue'] ?? 0);
                $productRevenue = (float) ($transactionRow['product_revenue'] ?? 0);
                $employeeFees = (float) ($transactionRow['employee_fees'] ?? 0);
                $expenses = (float) ($expenseRow['expenses'] ?? 0);
                $barberIncome = $serviceRevenue + $productRevenue - $employeeFees;

                return [
                    'month_number' => $monthNumber,
                    'service_revenue' => $serviceRevenue,
                    'product_revenue' => $productRevenue,
                    'expenses' => $expenses,
                    'employee_fees' => $employeeFees,
                    'barber_income' => $barberIncome,
                    'profit' => $barberIncome - $expenses,
                ];
            })
            ->values();
    }

    private function getMonthlyTransactionMetrics(int $year): Collection
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear()->toDateString();
        $endDate = Carbon::create($year, 1, 1)->endOfYear()->toDateString();
        ['year' => $yearExpression, 'month' => $monthExpression] = $this->getYearMonthExpressions('transactions.transaction_date');

        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->selectRaw('
                '.$monthExpression.' as month_number,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as service_revenue,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as product_revenue,
                COALESCE(SUM(transaction_items.commission_amount), 0) as employee_fees
            ', ['service', 'product'])
            ->groupByRaw($yearExpression.', '.$monthExpression)
            ->orderBy('month_number')
            ->get()
            ->map(function ($row): array {
                return [
                    'month_number' => (int) $row->month_number,
                    'service_revenue' => (float) $row->service_revenue,
                    'product_revenue' => (float) $row->product_revenue,
                    'employee_fees' => (float) $row->employee_fees,
                ];
            });
    }

    private function getMonthlyExpenseMetrics(int $year): Collection
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear()->toDateString();
        $endDate = Carbon::create($year, 1, 1)->endOfYear()->toDateString();
        ['year' => $yearExpression, 'month' => $monthExpression] = $this->getYearMonthExpressions('expenses.expense_date');

        return Expense::query()
            ->whereBetween('expenses.expense_date', [$startDate, $endDate])
            ->selectRaw('
                '.$monthExpression.' as month_number,
                COALESCE(SUM(expenses.amount), 0) as expenses
            ')
            ->groupByRaw($yearExpression.', '.$monthExpression)
            ->orderBy('month_number')
            ->get()
            ->map(function ($row): array {
                return [
                    'month_number' => (int) $row->month_number,
                    'expenses' => (float) $row->expenses,
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
