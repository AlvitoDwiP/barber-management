<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\TransactionItem;
use App\Services\Reports\Concerns\InteractsWithExactReportMoney;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductReportService
{
    use InteractsWithExactReportMoney;

    public function getTopProductOfMonth(?Carbon $month = null): ?array
    {
        $targetMonth = $month ?? now();
        $year = (int) $targetMonth->year;
        $monthNumber = (int) $targetMonth->month;
        $startDate = Carbon::create($year, $monthNumber, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $monthNumber, 1)->endOfMonth()->toDateString();
        $dateExpression = $this->getDateExpression('transactions.transaction_date');

        $topProduct = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('products', 'products.id', '=', 'transaction_items.product_id')
            ->where('transaction_items.item_type', 'product')
            ->whereNotNull('transaction_items.product_id')
            ->whereBetween(DB::raw($dateExpression), [$startDate, $endDate])
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

    public function getProductSalesReport(string $startDate, string $endDate, ?int $productId = null): Collection
    {
        $dateExpression = $this->getDateExpression('transactions.transaction_date');

        return TransactionItem::query()
            ->join('products', 'products.id', '=', 'transaction_items.product_id')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transaction_items.item_type', 'product')
            ->whereNotNull('transaction_items.product_id')
            ->whereBetween(DB::raw($dateExpression), [$startDate, $endDate])
            ->when($productId !== null, fn ($query) => $query->where('transaction_items.product_id', $productId))
            ->groupBy('products.id', 'products.name')
            ->selectRaw('
                products.name as product_name,
                COALESCE(SUM(transaction_items.qty), 0) as total_qty_sold,
                COALESCE(SUM(transaction_items.subtotal), 0) as total_revenue
            ')
            ->orderByDesc('total_revenue')
            ->orderBy('products.name')
            ->get()
            ->map(function ($row) {
                $totalQtySold = (int) $row->total_qty_sold;
                $totalRevenueMinorUnits = $this->moneyToMinorUnits($row->total_revenue);
                $averageSellingPriceMinorUnits = $totalQtySold > 0
                    ? $this->divideMinorUnits($totalRevenueMinorUnits, $totalQtySold, Money::ROUND_HALF_UP)
                    : 0;

                return [
                    'product_name' => $row->product_name,
                    'total_qty_sold' => $totalQtySold,
                    'average_selling_price' => $this->moneyFromMinorUnits($averageSellingPriceMinorUnits),
                    'total_revenue' => $this->moneyFromMinorUnits($totalRevenueMinorUnits),
                ];
            });
    }

    public function getProductsForFilter(): Collection
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function getDateExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "date({$column})";
        }

        return "DATE({$column})";
    }
}
