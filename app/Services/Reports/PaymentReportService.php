<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use App\Services\Reports\Concerns\InteractsWithExactReportMoney;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentReportService
{
    use InteractsWithExactReportMoney;

    public function getMonthlyPaymentSummary(?Carbon $month = null): array
    {
        return [
            'month' => ($month ?? now())->format('Y-m'),
            'cash' => '0.00',
            'qr' => '0.00',
            'cash_in' => '0.00',
            'total_transactions' => 0,
        ];
    }

    public function getPaymentMethodReport(int $year): Collection
    {
        $startDate = sprintf('%d-01-01', $year);
        $endDate = sprintf('%d-12-31', $year);
        ['year' => $yearExpression, 'month' => $monthExpression] = $this->getYearMonthExpressions('transaction_date');

        $rows = Transaction::query()
            ->selectRaw('
                '.$monthExpression.' as month_number,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN total_amount ELSE 0 END), 0) as total_cash,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN total_amount ELSE 0 END), 0) as total_qr,
                COUNT(id) as total_transactions
            ', ['cash', 'qr'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupByRaw($yearExpression.', '.$monthExpression)
            ->orderBy('month_number')
            ->get()
            ->map(function ($row) {
                $cashMinorUnits = $this->moneyToMinorUnits($row->total_cash);
                $qrMinorUnits = $this->moneyToMinorUnits($row->total_qr);

                return [
                    'month_number' => (int) $row->month_number,
                    'total_cash' => $this->moneyToDecimal($row->total_cash),
                    'total_qr' => $this->moneyToDecimal($row->total_qr),
                    'cash_in' => $this->moneyFromMinorUnits($cashMinorUnits + $qrMinorUnits),
                    'total_transactions' => (int) $row->total_transactions,
                ];
            })
            ->keyBy('month_number');

        return collect(range(1, 12))->map(function (int $month) use ($rows) {
            $row = $rows->get($month);

            return [
                'month_number' => $month,
                'total_cash' => (string) ($row['total_cash'] ?? '0.00'),
                'total_qr' => (string) ($row['total_qr'] ?? '0.00'),
                'cash_in' => (string) ($row['cash_in'] ?? '0.00'),
                'total_transactions' => (int) ($row['total_transactions'] ?? 0),
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
