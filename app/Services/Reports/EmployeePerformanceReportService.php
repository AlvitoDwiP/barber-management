<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;

class EmployeePerformanceReportService
{
    public function getTopEmployeeOfMonth(?Carbon $month = null): ?array
    {
        return [
            'month' => ($month ?? now())->format('Y-m'),
            'employee_id' => null,
            'employee_name' => null,
            'total_transactions' => 0,
            'total_revenue' => 0,
        ];
    }
}
