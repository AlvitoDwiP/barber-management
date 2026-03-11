<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;

class DailyReportService
{
    public function getTodaySummary(?Carbon $date = null): array
    {
        $reportDate = ($date ?? now())->toDateString();

        return [
            'date' => $reportDate,
            'total_transactions' => 0,
            'total_revenue' => 0,
        ];
    }
}
