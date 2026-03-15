<?php

namespace App\Services\Reports;

use App\Models\Expense;
use App\Models\TransactionItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonthlyReportService
{
    public function getCurrentMonthSummary(?Carbon $month = null): array
    {
        $targetMonth = $month ?? Carbon::now(config('app.timezone'));
        $year = (int) $targetMonth->year;
        $monthNumber = (int) $targetMonth->month;
        $startDate = Carbon::create($year, $monthNumber, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $monthNumber, 1)->endOfMonth()->toDateString();
        $transactionMetrics = $this->getTransactionMetricsForPeriod($startDate, $endDate);
        $expenses = $this->getExpenseTotalForPeriod($startDate, $endDate);

        return $this->buildMonthlySummary(
            serviceRevenue: $transactionMetrics['service_revenue'],
            productRevenue: $transactionMetrics['product_revenue'],
            expenses: $expenses,
            employeeFees: $transactionMetrics['employee_fees'],
        );
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

                return [
                    'month_number' => $monthNumber,
                    ...$this->buildMonthlySummary(
                        serviceRevenue: (float) ($transactionRow['service_revenue'] ?? 0),
                        productRevenue: (float) ($transactionRow['product_revenue'] ?? 0),
                        expenses: (float) ($expenseRow['expenses'] ?? 0),
                        employeeFees: (float) ($transactionRow['employee_fees'] ?? 0),
                    ),
                ];
            })
            ->values();
    }

    private function getTransactionMetricsForPeriod(string $startDate, string $endDate): array
    {
        $metrics = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->selectRaw('
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as service_revenue,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as product_revenue,
                COALESCE(SUM(transaction_items.commission_amount), 0) as employee_fees
            ', ['service', 'product'])
            ->first();

        return [
            'service_revenue' => (float) ($metrics->service_revenue ?? 0),
            'product_revenue' => (float) ($metrics->product_revenue ?? 0),
            'employee_fees' => (float) ($metrics->employee_fees ?? 0),
        ];
    }

    private function getExpenseTotalForPeriod(string $startDate, string $endDate): float
    {
        return (float) Expense::query()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');
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

    private function buildMonthlySummary(
        float $serviceRevenue,
        float $productRevenue,
        float $expenses,
        float $employeeFees
    ): array {
        $barberIncome = $serviceRevenue + $productRevenue - $employeeFees;

        return [
            'service_revenue' => $serviceRevenue,
            'product_revenue' => $productRevenue,
            'expenses' => $expenses,
            'employee_fees' => $employeeFees,
            'barber_income' => $barberIncome,
            'profit' => $barberIncome - $expenses,
        ];
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
