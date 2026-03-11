<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PaymentReportService
{
    public function getMonthlyPaymentSummary(?Carbon $month = null): array
    {
        return [
            'month' => ($month ?? now())->format('Y-m'),
            'cash' => 0,
            'qr' => 0,
            'total_transactions' => 0,
        ];
    }

    public function getPaymentMethodReport(int $year): Collection
    {
        $startDate = sprintf('%d-01-01', $year);
        $endDate = sprintf('%d-12-31', $year);

        $rows = Transaction::query()
            ->selectRaw('
                MONTH(transaction_date) as month_number,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN total_amount ELSE 0 END), 0) as total_cash,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN total_amount ELSE 0 END), 0) as total_qr,
                COUNT(id) as total_transactions
            ', ['cash', 'qr'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupByRaw('YEAR(transaction_date), MONTH(transaction_date)')
            ->orderBy('month_number')
            ->get()
            ->map(function ($row) {
                return [
                    'month_number' => (int) $row->month_number,
                    'total_cash' => (float) $row->total_cash,
                    'total_qr' => (float) $row->total_qr,
                    'total_transactions' => (int) $row->total_transactions,
                ];
            })
            ->keyBy('month_number');

        return collect(range(1, 12))->map(function (int $month) use ($rows) {
            $row = $rows->get($month);

            return [
                'month_number' => $month,
                'total_cash' => (float) ($row['total_cash'] ?? 0),
                'total_qr' => (float) ($row['total_qr'] ?? 0),
                'total_transactions' => (int) ($row['total_transactions'] ?? 0),
            ];
        });
    }
}
