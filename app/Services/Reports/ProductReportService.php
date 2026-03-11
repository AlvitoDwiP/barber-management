<?php

namespace App\Services\Reports;

use App\Models\TransactionDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProductReportService
{
    public function getTopProductOfMonth(?Carbon $month = null): ?array
    {
        $targetMonth = $month ?? now();
        $year = (int) $targetMonth->year;
        $monthNumber = (int) $targetMonth->month;
        $startDate = Carbon::create($year, $monthNumber, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $monthNumber, 1)->endOfMonth()->toDateString();

        $topProduct = TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_items.product_id')
            ->where('transaction_items.item_type', 'product')
            ->whereNotNull('transaction_items.product_id')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
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

    public function getProductSalesReport(): Collection
    {
        return TransactionDetail::query()
            ->join('products', 'products.id', '=', 'transaction_items.product_id')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transaction_items.item_type', 'product')
            ->whereNotNull('transaction_items.product_id')
            ->groupBy('products.id', 'products.name', 'products.stock')
            ->selectRaw('
                products.name as product_name,
                COALESCE(SUM(transaction_items.qty), 0) as total_qty_sold,
                COALESCE(SUM(transaction_items.subtotal), 0) as total_revenue,
                products.stock as stock_remaining
            ')
            ->orderByDesc('total_qty_sold')
            ->get()
            ->map(function ($row) {
                return [
                    'product_name' => $row->product_name,
                    'total_qty_sold' => (int) $row->total_qty_sold,
                    'total_revenue' => (float) $row->total_revenue,
                    'stock_remaining' => (int) $row->stock_remaining,
                ];
            });
    }
}
