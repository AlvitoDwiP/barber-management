<?php

namespace App\Services\Reports;

use App\Models\Expense;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    public function getTodaySummary(?Carbon $date = null): array
    {
        $reportDate = ($date ?? Carbon::today(config('app.timezone')))->toDateString();

        $summary = Transaction::query()
            ->selectRaw('
                COALESCE(SUM(total_amount), 0) as today_revenue,
                COUNT(DISTINCT id) as today_transactions,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN total_amount ELSE 0 END), 0) as today_cash,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN total_amount ELSE 0 END), 0) as today_qr
            ', ['cash', 'qr'])
            ->whereDate('transaction_date', $reportDate)
            ->first();

        return [
            'today_revenue' => (float) ($summary->today_revenue ?? 0),
            'today_transactions' => (int) ($summary->today_transactions ?? 0),
            'today_cash' => (float) ($summary->today_cash ?? 0),
            'today_qr' => (float) ($summary->today_qr ?? 0),
        ];
    }

    public function getDailyReport(string $startDate, string $endDate): array
    {
        $rows = $this->buildDailyRows($startDate, $endDate);

        return [
            'rows' => $rows,
            'summary' => $this->buildDailySummary($rows, $startDate, $endDate),
        ];
    }

    public function getDailyRevenueReport(string $startDate, string $endDate): Collection
    {
        return $this->getDailyReport($startDate, $endDate)['rows'];
    }

    private function buildDailyRows(string $startDate, string $endDate): Collection
    {
        $revenueRows = $this->getRevenueMetricsByDate($startDate, $endDate)->keyBy('report_date');
        $paymentRows = $this->getPaymentMetricsByDate($startDate, $endDate)->keyBy('report_date');
        $expenseRows = $this->getExpenseMetricsByDate($startDate, $endDate)->keyBy('report_date');

        return $revenueRows->keys()
            ->merge($paymentRows->keys())
            ->merge($expenseRows->keys())
            ->unique()
            ->sort()
            ->values()
            ->map(function (string $reportDate) use ($revenueRows, $paymentRows, $expenseRows): array {
                $revenueRow = $revenueRows->get($reportDate, []);
                $paymentRow = $paymentRows->get($reportDate, []);
                $expenseRow = $expenseRows->get($reportDate, []);
                $serviceRevenue = (float) ($revenueRow['service_revenue'] ?? 0);
                $productRevenue = (float) ($revenueRow['product_revenue'] ?? 0);
                $totalRevenue = $serviceRevenue + $productRevenue;
                $expenses = (float) ($expenseRow['expenses'] ?? 0);

                return [
                    'report_date' => $reportDate,
                    'total_transactions' => (int) ($paymentRow['total_transactions'] ?? 0),
                    'service_revenue' => $serviceRevenue,
                    'product_revenue' => $productRevenue,
                    'total_revenue' => $totalRevenue,
                    'cash' => (float) ($paymentRow['cash'] ?? 0),
                    'qr' => (float) ($paymentRow['qr'] ?? 0),
                    'expenses' => $expenses,
                    'net_income' => $totalRevenue - $expenses,
                ];
            })
            ->values();
    }

    private function buildDailySummary(Collection $rows, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return [
            'total_days_in_period' => (int) ($start->diffInDays($end) + 1),
            'total_transactions' => (int) $rows->sum('total_transactions'),
            'service_revenue' => (float) $rows->sum('service_revenue'),
            'product_revenue' => (float) $rows->sum('product_revenue'),
            'total_revenue' => (float) $rows->sum('total_revenue'),
            'cash' => (float) $rows->sum('cash'),
            'qr' => (float) $rows->sum('qr'),
            'expenses' => (float) $rows->sum('expenses'),
            'net_income' => (float) $rows->sum('net_income'),
        ];
    }

    private function getRevenueMetricsByDate(string $startDate, string $endDate): Collection
    {
        [$rangeStart, $rangeEnd] = $this->getRangeBounds($startDate, $endDate);
        $dateExpression = $this->getDateExpression('transactions.transaction_date');

        return TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->whereBetween('transactions.transaction_date', [$rangeStart, $rangeEnd])
            ->selectRaw('
                '.$dateExpression.' as report_date,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as service_revenue,
                COALESCE(SUM(CASE WHEN transaction_items.item_type = ? THEN transaction_items.subtotal ELSE 0 END), 0) as product_revenue
            ', ['service', 'product'])
            ->groupByRaw($dateExpression)
            ->orderByRaw($dateExpression)
            ->get()
            ->map(function ($row): array {
                return [
                    'report_date' => Carbon::parse($row->report_date)->toDateString(),
                    'service_revenue' => (float) $row->service_revenue,
                    'product_revenue' => (float) $row->product_revenue,
                ];
            });
    }

    private function getPaymentMetricsByDate(string $startDate, string $endDate): Collection
    {
        [$rangeStart, $rangeEnd] = $this->getRangeBounds($startDate, $endDate);
        $dateExpression = $this->getDateExpression('transaction_date');

        return Transaction::query()
            ->whereBetween('transaction_date', [$rangeStart, $rangeEnd])
            ->selectRaw('
                '.$dateExpression.' as report_date,
                COUNT(id) as total_transactions,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN total_amount ELSE 0 END), 0) as cash,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN total_amount ELSE 0 END), 0) as qr
            ', ['cash', 'qr'])
            ->groupByRaw($dateExpression)
            ->orderByRaw($dateExpression)
            ->get()
            ->map(function ($row): array {
                return [
                    'report_date' => Carbon::parse($row->report_date)->toDateString(),
                    'total_transactions' => (int) $row->total_transactions,
                    'cash' => (float) $row->cash,
                    'qr' => (float) $row->qr,
                ];
            });
    }

    private function getExpenseMetricsByDate(string $startDate, string $endDate): Collection
    {
        [$rangeStart, $rangeEnd] = $this->getRangeBounds($startDate, $endDate);
        $dateExpression = $this->getDateExpression('expense_date');

        return Expense::query()
            ->whereBetween('expense_date', [$rangeStart, $rangeEnd])
            ->selectRaw('
                '.$dateExpression.' as report_date,
                COALESCE(SUM(amount), 0) as expenses
            ')
            ->groupByRaw($dateExpression)
            ->orderByRaw($dateExpression)
            ->get()
            ->map(function ($row): array {
                return [
                    'report_date' => Carbon::parse($row->report_date)->toDateString(),
                    'expenses' => (float) $row->expenses,
                ];
            });
    }

    private function getRangeBounds(string $startDate, string $endDate): array
    {
        return [
            Carbon::parse($startDate)->startOfDay()->toDateTimeString(),
            Carbon::parse($endDate)->endOfDay()->toDateTimeString(),
        ];
    }

    private function getDateExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "date({$column})";
        }

        return "DATE({$column})";
    }
}
