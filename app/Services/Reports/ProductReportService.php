<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;

class ProductReportService
{
    public function getTopProductOfMonth(?Carbon $month = null): ?array
    {
        return [
            'month' => ($month ?? now())->format('Y-m'),
            'product_id' => null,
            'product_name' => null,
            'total_qty' => 0,
            'total_revenue' => 0,
        ];
    }
}
