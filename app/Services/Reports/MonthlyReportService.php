<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;

class MonthlyReportService
{
    public function getMonthlySummary(?Carbon $month = null): array
    {
        $reportMonth = ($month ?? now())->format('Y-m');

        return [
            'month' => $reportMonth,
            'total_transactions' => 0,
            'total_revenue' => 0,
        ];
    }
}
