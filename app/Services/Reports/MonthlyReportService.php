<?php

namespace App\Services\Reports;

use App\Models\Expense;
use App\Models\TransactionItem;
use App\Services\Reports\Concerns\InteractsWithExactReportMoney;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonthlyReportService
{
    use InteractsWithExactReportMoney;

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
                        serviceRevenue: (string) ($transactionRow['service_revenue'] ?? '0.00'),
                        productRevenue: (string) ($transactionRow['product_revenue'] ?? '0.00'),
                        expenses: (string) ($expenseRow['expenses'] ?? '0.00'),
                        employeeFees: (string) ($transactionRow['employee_fees'] ?? '0.00'),
                    ),
                ];
            })
            ->values();
    }

    private function getTransactionMetricsForPeriod(string $startDate, string $endDate): array
    {
        // Monthly fees and profit must use frozen transaction item snapshots, not live master commission rules.
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
            'service_revenue' => $this->moneyToDecimal($metrics->service_revenue ?? 0),
            'product_revenue' => $this->moneyToDecimal($metrics->product_revenue ?? 0),
            'employee_fees' => $this->moneyToDecimal($metrics->employee_fees ?? 0),
        ];
    }

    private function getExpenseTotalForPeriod(string $startDate, string $endDate): string
    {
        return $this->moneyToDecimal(Expense::query()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount'));
    }

    private function getMonthlyTransactionMetrics(int $year): Collection
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear()->toDateString();
        $endDate = Carbon::create($year, 1, 1)->endOfYear()->toDateString();
        ['year' => $yearExpression, 'month' => $monthExpression] = $this->getYearMonthExpressions('transactions.transaction_date');

        // Monthly rollups stay historical by aggregating transaction item snapshots.
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
                    'service_revenue' => $this->moneyToDecimal($row->service_revenue),
                    'product_revenue' => $this->moneyToDecimal($row->product_revenue),
                    'employee_fees' => $this->moneyToDecimal($row->employee_fees),
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
                    'expenses' => $this->moneyToDecimal($row->expenses),
                ];
            });
    }

    private function buildMonthlySummary(
        string $serviceRevenue,
        string $productRevenue,
        string $expenses,
        string $employeeFees
    ): array {
        $serviceRevenueMinorUnits = $this->moneyToMinorUnits($serviceRevenue);
        $productRevenueMinorUnits = $this->moneyToMinorUnits($productRevenue);
        $expensesMinorUnits = $this->moneyToMinorUnits($expenses);
        $employeeFeesMinorUnits = $this->moneyToMinorUnits($employeeFees);
        $totalRevenueMinorUnits = $serviceRevenueMinorUnits + $productRevenueMinorUnits;
        $barberIncomeMinorUnits = $totalRevenueMinorUnits - $employeeFeesMinorUnits;
        $netProfitMinorUnits = $totalRevenueMinorUnits - $employeeFeesMinorUnits - $expensesMinorUnits;

        return [
            'service_revenue' => $this->moneyFromMinorUnits($serviceRevenueMinorUnits),
            'product_revenue' => $this->moneyFromMinorUnits($productRevenueMinorUnits),
            'total_revenue' => $this->moneyFromMinorUnits($totalRevenueMinorUnits),
            'expenses' => $this->moneyFromMinorUnits($expensesMinorUnits),
            'employee_fees' => $this->moneyFromMinorUnits($employeeFeesMinorUnits),
            'employee_commissions' => $this->moneyFromMinorUnits($employeeFeesMinorUnits),
            'barber_income' => $this->moneyFromMinorUnits($barberIncomeMinorUnits),
            'profit' => $this->moneyFromMinorUnits($netProfitMinorUnits),
            'net_profit' => $this->moneyFromMinorUnits($netProfitMinorUnits),
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
