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

    public function __construct(
        private readonly BusinessMetricService $businessMetricService,
    ) {
    }

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
            operationalExpenses: $expenses,
            barberCommissions: $transactionMetrics['barber_commissions'],
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
                        operationalExpenses: (string) ($expenseRow['expenses'] ?? '0.00'),
                        barberCommissions: (string) ($transactionRow['barber_commissions'] ?? '0.00'),
                    ),
                ];
            })
            ->values();
    }

    private function getTransactionMetricsForPeriod(string $startDate, string $endDate): array
    {
        // Monthly business metrics must use frozen transaction item snapshots, not live master commission rules.
        $metrics = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->selectRaw('
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as service_revenue,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as product_revenue,
                COALESCE(SUM(transaction_items.commission_amount), 0) as barber_commissions
            ', ['service', 'product'])
            ->first();

        return [
            'service_revenue' => $this->moneyToDecimal($metrics->service_revenue ?? 0),
            'product_revenue' => $this->moneyToDecimal($metrics->product_revenue ?? 0),
            'barber_commissions' => $this->moneyToDecimal($metrics->barber_commissions ?? 0),
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
                COALESCE(SUM(transaction_items.commission_amount), 0) as barber_commissions
            ', ['service', 'product'])
            ->groupByRaw($yearExpression.', '.$monthExpression)
            ->orderBy('month_number')
            ->get()
            ->map(function ($row): array {
                return [
                    'month_number' => (int) $row->month_number,
                    'service_revenue' => $this->moneyToDecimal($row->service_revenue),
                    'product_revenue' => $this->moneyToDecimal($row->product_revenue),
                    'barber_commissions' => $this->moneyToDecimal($row->barber_commissions),
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
        string $operationalExpenses,
        string $barberCommissions
    ): array {
        return $this->businessMetricService->buildOperatingPerformanceSummary(
            serviceRevenue: $serviceRevenue,
            productRevenue: $productRevenue,
            barberCommissions: $barberCommissions,
            operationalExpenses: $operationalExpenses,
        );
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
