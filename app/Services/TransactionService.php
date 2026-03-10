<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    public function storeTransaction(array $validatedData): Transaction
    {
        return DB::transaction(function () use ($validatedData): Transaction {
            $serviceIds = $this->extractServiceIds($validatedData);
            $productQtyById = $this->extractProductQuantities($validatedData);

            if ($serviceIds === [] && $productQtyById === []) {
                throw new DomainException(
                    'Transaksi harus memiliki minimal satu layanan atau satu produk dengan qty lebih dari 0.'
                );
            }

            $transaction = Transaction::query()->create([
                'transaction_code' => $this->generateTransactionCode(),
                'transaction_date' => $validatedData['transaction_date'],
                'employee_id' => $validatedData['employee_id'],
                'payment_method' => $validatedData['payment_method'],
                'subtotal_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
            ]);

            $totalAmount = 0.0;
            $totalAmount += $this->storeServiceDetails($transaction, $serviceIds);
            $totalAmount += $this->storeProductDetails($transaction, $productQtyById);

            $transaction->update([
                'subtotal_amount' => $totalAmount,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
            ]);

            return $transaction;
        });
    }

    private function storeServiceDetails(Transaction $transaction, array $serviceIds): float
    {
        if ($serviceIds === []) {
            return 0.0;
        }

        $services = Service::query()
            ->whereIn('id', $serviceIds)
            ->get()
            ->keyBy('id');

        $total = 0.0;

        foreach ($serviceIds as $serviceId) {
            $service = $services->get($serviceId);

            if (! $service) {
                throw new DomainException("Layanan dengan ID {$serviceId} tidak ditemukan.");
            }

            $itemPrice = (float) $service->price;
            $subtotal = $itemPrice;
            $commission = $itemPrice * 0.5;

            $transaction->transactionDetails()->create([
                'item_type' => 'service',
                'service_id' => $service->id,
                'product_id' => null,
                'item_name' => $service->name,
                'unit_price' => $itemPrice,
                'qty' => 1,
                'subtotal' => $subtotal,
                'commission_amount' => $commission,
            ]);

            $total += $subtotal;
        }

        return $total;
    }

    private function storeProductDetails(Transaction $transaction, array $productQtyById): float
    {
        if ($productQtyById === []) {
            return 0.0;
        }

        $products = Product::query()
            ->whereIn('id', array_keys($productQtyById))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $total = 0.0;

        foreach ($productQtyById as $productId => $qty) {
            $product = $products->get($productId);

            if (! $product) {
                throw new DomainException("Produk dengan ID {$productId} tidak ditemukan.");
            }

            if ($product->stock < $qty) {
                throw new DomainException(
                    "Stok produk {$product->name} tidak cukup. Tersedia {$product->stock}, diminta {$qty}."
                );
            }

            $itemPrice = (float) $product->price;
            $subtotal = $itemPrice * $qty;
            $commission = 5000 * $qty;

            $product->decrement('stock', $qty);

            $transaction->transactionDetails()->create([
                'item_type' => 'product',
                'service_id' => null,
                'product_id' => $product->id,
                'item_name' => $product->name,
                'unit_price' => $itemPrice,
                'qty' => $qty,
                'subtotal' => $subtotal,
                'commission_amount' => $commission,
            ]);

            $total += $subtotal;
        }

        return $total;
    }

    private function extractServiceIds(array $validatedData): array
    {
        return collect($validatedData['services'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function extractProductQuantities(array $validatedData): array
    {
        return collect($validatedData['products'] ?? [])
            ->mapWithKeys(fn ($qty, $productId) => [(int) $productId => (int) $qty])
            ->filter(fn ($qty, $productId) => $productId > 0 && $qty > 0)
            ->all();
    }

    private function generateTransactionCode(): string
    {
        do {
            $code = 'TRX-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
        } while (Transaction::query()->where('transaction_code', $code)->exists());

        return $code;
    }
}
