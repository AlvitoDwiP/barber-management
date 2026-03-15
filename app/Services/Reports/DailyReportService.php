<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

    public function getDailyRevenueReport(string $startDate, string $endDate): Collection
    {
        return Transaction::query()
            ->selectRaw('
                transaction_date,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COUNT(DISTINCT id) as total_transactions,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN total_amount ELSE 0 END), 0) as total_cash,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN total_amount ELSE 0 END), 0) as total_qr
            ', ['cash', 'qr'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('transaction_date')
            ->orderBy('transaction_date')
            ->get();
    }
}
