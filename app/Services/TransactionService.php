<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TransactionService
{
    private const MINIMUM_ITEM_MESSAGE = 'Transaksi harus berisi minimal 1 item: pilih minimal 1 layanan atau isi qty produk lebih dari 0.';

    public function storeTransaction(array $validatedData): Transaction
    {
        return DB::transaction(function () use ($validatedData): Transaction {
            [$serviceIds, $productQtyById] = $this->extractTransactionItems($validatedData);
            $this->assertHasMinimumItems($serviceIds, $productQtyById);

            $transaction = Transaction::query()->create([
                'transaction_code' => $this->generateTransactionCode(),
                'transaction_date' => $validatedData['transaction_date'] ?? now()->toDateString(),
                'employee_id' => $validatedData['employee_id'],
                'payment_method' => $validatedData['payment_method'],
                'subtotal_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
            ]);

            $totalAmount = 0.0;
            $totalAmount += $this->storeServiceDetails($transaction, $serviceIds);
            $totalAmount += $this->storeProductDetails($transaction, $productQtyById);

            $this->finalizeTransactionTotals($transaction, $totalAmount);

            return $transaction;
        });
    }

    public function updateTransaction(Transaction $transaction, array $validatedData): Transaction
    {
        return DB::transaction(function () use ($transaction, $validatedData): Transaction {
            $transaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            [$serviceIds, $productQtyById] = $this->extractTransactionItems($validatedData);
            $this->assertHasMinimumItems($serviceIds, $productQtyById);

            $existingDetails = $transaction->transactionDetails()
                ->select('id', 'item_type', 'product_id', 'qty')
                ->get();

            $this->restoreOldProductStocks($existingDetails);
            $transaction->transactionDetails()->delete();

            $transaction->update([
                'transaction_date' => $validatedData['transaction_date'] ?? now()->toDateString(),
                'employee_id' => $validatedData['employee_id'],
                'payment_method' => $validatedData['payment_method'],
                'subtotal_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
            ]);

            $totalAmount = 0.0;
            $totalAmount += $this->storeServiceDetails($transaction, $serviceIds);
            $totalAmount += $this->storeProductDetails($transaction, $productQtyById);

            $this->finalizeTransactionTotals($transaction, $totalAmount);

            return $transaction;
        });
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $transaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingDetails = $transaction->transactionDetails()
                ->select('id', 'item_type', 'product_id', 'qty')
                ->get();

            $this->restoreOldProductStocks($existingDetails);

            $transaction->transactionDetails()->delete();
            $transaction->delete();
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

            // Snapshot diambil dari data master service, bukan dari request frontend.
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

            // Snapshot diambil dari data master product, bukan dari request frontend.
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

    private function restoreOldProductStocks(Collection $existingDetails): void
    {
        $restoreQtyByProductId = $existingDetails
            ->where('item_type', 'product')
            ->filter(fn ($detail) => $detail->product_id !== null && (int) $detail->qty > 0)
            ->groupBy('product_id')
            ->map(fn ($rows) => (int) $rows->sum('qty'))
            ->all();

        if ($restoreQtyByProductId === []) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', array_keys($restoreQtyByProductId))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($restoreQtyByProductId as $productId => $qtyToRestore) {
            $product = $products->get((int) $productId);

            if (! $product) {
                continue;
            }

            $product->increment('stock', $qtyToRestore);
        }
    }

    private function assertHasMinimumItems(array $serviceIds, array $productQtyById): void
    {
        if ($serviceIds === [] && $productQtyById === []) {
            throw new DomainException(self::MINIMUM_ITEM_MESSAGE);
        }
    }

    private function extractTransactionItems(array $validatedData): array
    {
        $serviceIds = $this->extractServiceIds($validatedData);
        $productQtyById = $this->extractProductQuantities($validatedData);

        return [$serviceIds, $productQtyById];
    }

    private function finalizeTransactionTotals(Transaction $transaction, float $totalAmount): void
    {
        $transaction->update([
            'subtotal_amount' => $totalAmount,
            'discount_amount' => 0,
            'total_amount' => $totalAmount,
        ]);
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
