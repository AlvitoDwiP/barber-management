<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;

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
}
