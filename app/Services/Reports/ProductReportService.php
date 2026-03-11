<?php

namespace App\Services\Reports;

use App\Models\TransactionDetail;
use Illuminate\Support\Carbon;

class ProductReportService
{
    public function getTopProductOfMonth(?Carbon $month = null): ?array
    {
        $targetMonth = $month ?? now();
        $year = (int) $targetMonth->year;
        $monthNumber = (int) $targetMonth->month;

        $topProduct = TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_items.product_id')
            ->where('transaction_items.item_type', 'product')
            ->whereNotNull('transaction_items.product_id')
            ->whereYear('transactions.transaction_date', $year)
            ->whereMonth('transactions.transaction_date', $monthNumber)
            ->groupBy('transaction_items.product_id', 'products.name')
            ->selectRaw('
                products.name as product_name,
                COALESCE(SUM(transaction_items.qty), 0) as qty_sold
            ')
            ->orderByDesc('qty_sold')
            ->first();

        return [
            'product_name' => $topProduct?->product_name,
            'qty_sold' => (int) ($topProduct?->qty_sold ?? 0),
        ];
    }
}
