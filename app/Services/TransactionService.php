<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PayrollPeriod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class TransactionService
{
    private const MINIMUM_ITEM_MESSAGE = 'Transaksi harus berisi minimal 1 item: pilih minimal 1 layanan atau isi qty produk lebih dari 0.';
    private const CLOSED_PAYROLL_MESSAGE = 'Transaksi yang sudah terikat ke payroll tertutup tidak dapat diubah atau dihapus.';
    private const MONEY_SCALE = 2;
    private const MINOR_UNIT_MULTIPLIER = 100;
    private const SERVICE_COMMISSION_BASIS_POINTS = 5000;
    private const PRODUCT_COMMISSION_PER_UNIT = '5000.00';
    private const TRANSACTION_CODE_RETRY_LIMIT = 5;

    public function storeTransaction(array $validatedData): Transaction
    {
        return DB::transaction(function () use ($validatedData): Transaction {
            [$serviceIds, $productQtyById] = $this->extractTransactionItems($validatedData);
            $this->assertHasMinimumItems($serviceIds, $productQtyById);

            $transaction = $this->createTransactionRecord($validatedData);

            $this->syncTransactionDetails($transaction, $validatedData);

            return $transaction;
        });
    }

    public function updateTransaction(Transaction $transaction, array $validatedData): Transaction
    {
        return DB::transaction(function () use ($transaction, $validatedData): Transaction {
            $transaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->with('payrollPeriod:id,status')
                ->firstOrFail();

            $this->assertTransactionCanBeMutated($transaction);
            $this->syncTransactionDetails($transaction, $validatedData, $this->getExistingDetails($transaction));

            return $transaction;
        });
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $transaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->with('payrollPeriod:id,status')
                ->firstOrFail();

            $this->assertTransactionCanBeMutated($transaction);
            $existingDetails = $this->getExistingDetails($transaction);
            $lockedProducts = $this->lockProducts($this->extractProductIdsFromDetails($existingDetails));

            $this->restoreOldProductStocks($existingDetails, $lockedProducts);

            $transaction->transactionDetails()->delete();
            $transaction->delete();
        });
    }

    private function syncTransactionDetails(
        Transaction $transaction,
        array $validatedData,
        ?Collection $existingDetails = null
    ): void {
        [$serviceIds, $productQtyById] = $this->extractTransactionItems($validatedData);
        $this->assertHasMinimumItems($serviceIds, $productQtyById);

        $existingDetails ??= collect();
        $lockedProducts = $this->lockProducts($this->mergeProductIdsForLock($existingDetails, $productQtyById));

        $this->restoreOldProductStocks($existingDetails, $lockedProducts);
        $transaction->transactionDetails()->delete();
        $this->updateTransactionHeader($transaction, $validatedData);

        $totalMinorUnits = 0;
        $totalMinorUnits += $this->storeServiceDetails($transaction, $serviceIds, $existingDetails);
        $totalMinorUnits += $this->storeProductDetails($transaction, $productQtyById, $existingDetails, $lockedProducts);

        $this->finalizeTransactionTotals($transaction, $totalMinorUnits);
    }

    private function createTransactionRecord(array $validatedData): Transaction
    {
        $attributes = $this->buildTransactionAttributes($validatedData);

        for ($attempt = 1; $attempt <= self::TRANSACTION_CODE_RETRY_LIMIT; $attempt++) {
            try {
                return Transaction::query()->create([
                    ...$attributes,
                    'transaction_code' => $this->generateTransactionCode($attributes['transaction_date']),
                ]);
            } catch (QueryException $exception) {
                if (! $this->isTransactionCodeCollision($exception) || $attempt === self::TRANSACTION_CODE_RETRY_LIMIT) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Gagal membuat kode transaksi yang unik.');
    }

    private function updateTransactionHeader(Transaction $transaction, array $validatedData): void
    {
        $transaction->update($this->buildTransactionAttributes($validatedData));
    }

    private function buildTransactionAttributes(array $validatedData): array
    {
        return [
            'transaction_date' => Carbon::parse($validatedData['transaction_date'])->toDateString(),
            'employee_id' => $validatedData['employee_id'],
            'payment_method' => $validatedData['payment_method'],
            'subtotal_amount' => $this->formatMoneyFromMinorUnits(0),
            'discount_amount' => $this->formatMoneyFromMinorUnits(0),
            'total_amount' => $this->formatMoneyFromMinorUnits(0),
        ];
    }

    private function storeServiceDetails(Transaction $transaction, array $serviceIds, Collection $existingDetails): int
    {
        if ($serviceIds === []) {
            return 0;
        }

        $existingServiceDetails = $existingDetails
            ->where('item_type', 'service')
            ->filter(fn (TransactionDetail $detail) => $detail->service_id !== null)
            ->keyBy('service_id');

        $services = Service::query()
            ->whereIn('id', $serviceIds)
            ->get()
            ->keyBy('id');

        $totalMinorUnits = 0;

        foreach ($serviceIds as $serviceId) {
            $existingDetail = $existingServiceDetails->get($serviceId);
            $detailAttributes = $existingDetail !== null
                ? $this->buildReusedServiceDetailAttributes($existingDetail)
                : $this->buildFreshServiceDetailAttributes($services->get($serviceId), $serviceId);

            $transaction->transactionDetails()->create($detailAttributes);
            $totalMinorUnits += $this->moneyToMinorUnits($detailAttributes['subtotal']);
        }

        return $totalMinorUnits;
    }

    private function storeProductDetails(
        Transaction $transaction,
        array $productQtyById,
        Collection $existingDetails,
        Collection $lockedProducts
    ): int {
        if ($productQtyById === []) {
            return 0;
        }

        $existingProductDetails = $existingDetails
            ->where('item_type', 'product')
            ->filter(fn (TransactionDetail $detail) => $detail->product_id !== null)
            ->keyBy('product_id');

        $totalMinorUnits = 0;

        foreach ($productQtyById as $productId => $qty) {
            $product = $lockedProducts->get($productId);

            if (! $product) {
                throw new DomainException("Produk dengan ID {$productId} tidak ditemukan.");
            }

            $this->assertStockAvailable($product, $qty);
            $existingDetail = $existingProductDetails->get($productId);
            $detailAttributes = $existingDetail !== null && (int) $existingDetail->qty === $qty
                ? $this->buildReusedProductDetailAttributes($existingDetail)
                : $this->buildFreshProductDetailAttributes($product, $qty);

            $currentStock = (int) $product->stock;
            $product->decrement('stock', $qty);
            $product->stock = max(0, $currentStock - $qty);

            $transaction->transactionDetails()->create($detailAttributes);
            $totalMinorUnits += $this->moneyToMinorUnits($detailAttributes['subtotal']);
        }

        return $totalMinorUnits;
    }

    private function buildFreshServiceDetailAttributes(?Service $service, int $serviceId): array
    {
        if (! $service) {
            throw new DomainException("Layanan dengan ID {$serviceId} tidak ditemukan.");
        }

        $unitPriceMinorUnits = $this->moneyToMinorUnits($service->price);
        $subtotalMinorUnits = $unitPriceMinorUnits;
        $commissionMinorUnits = $this->calculatePercentageMinorUnits(
            $unitPriceMinorUnits,
            self::SERVICE_COMMISSION_BASIS_POINTS
        );

        return [
            'item_type' => 'service',
            'service_id' => $service->id,
            'product_id' => null,
            'item_name' => $service->name,
            'unit_price' => $this->formatMoneyFromMinorUnits($unitPriceMinorUnits),
            'qty' => 1,
            'subtotal' => $this->formatMoneyFromMinorUnits($subtotalMinorUnits),
            'commission_amount' => $this->formatMoneyFromMinorUnits($commissionMinorUnits),
        ];
    }

    private function buildFreshProductDetailAttributes(Product $product, int $qty): array
    {
        $unitPriceMinorUnits = $this->moneyToMinorUnits($product->price);
        $subtotalMinorUnits = $unitPriceMinorUnits * $qty;
        $commissionMinorUnits = $this->moneyToMinorUnits(self::PRODUCT_COMMISSION_PER_UNIT) * $qty;

        return [
            'item_type' => 'product',
            'service_id' => null,
            'product_id' => $product->id,
            'item_name' => $product->name,
            'unit_price' => $this->formatMoneyFromMinorUnits($unitPriceMinorUnits),
            'qty' => $qty,
            'subtotal' => $this->formatMoneyFromMinorUnits($subtotalMinorUnits),
            'commission_amount' => $this->formatMoneyFromMinorUnits($commissionMinorUnits),
        ];
    }

    private function buildReusedServiceDetailAttributes(TransactionDetail $detail): array
    {
        return [
            'item_type' => 'service',
            'service_id' => $detail->service_id,
            'product_id' => null,
            'item_name' => $detail->item_name,
            'unit_price' => $this->formatMoneyValue($detail->unit_price),
            'qty' => 1,
            'subtotal' => $this->formatMoneyValue($detail->subtotal),
            'commission_amount' => $this->formatMoneyValue($detail->commission_amount),
        ];
    }

    private function buildReusedProductDetailAttributes(TransactionDetail $detail): array
    {
        return [
            'item_type' => 'product',
            'service_id' => null,
            'product_id' => $detail->product_id,
            'item_name' => $detail->item_name,
            'unit_price' => $this->formatMoneyValue($detail->unit_price),
            'qty' => (int) $detail->qty,
            'subtotal' => $this->formatMoneyValue($detail->subtotal),
            'commission_amount' => $this->formatMoneyValue($detail->commission_amount),
        ];
    }

    private function restoreOldProductStocks(Collection $existingDetails, Collection $lockedProducts): void
    {
        $restoreQtyByProductId = $existingDetails
            ->where('item_type', 'product')
            ->filter(fn (TransactionDetail $detail) => $detail->product_id !== null && (int) $detail->qty > 0)
            ->groupBy('product_id')
            ->map(fn (Collection $rows) => (int) $rows->sum('qty'));

        foreach ($restoreQtyByProductId as $productId => $qtyToRestore) {
            $product = $lockedProducts->get((int) $productId);

            if (! $product) {
                continue;
            }

            $currentStock = (int) $product->stock;
            $product->increment('stock', $qtyToRestore);
            $product->stock = $currentStock + $qtyToRestore;
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

    private function finalizeTransactionTotals(Transaction $transaction, int $totalMinorUnits): void
    {
        $transaction->update([
            'subtotal_amount' => $this->formatMoneyFromMinorUnits($totalMinorUnits),
            'discount_amount' => $this->formatMoneyFromMinorUnits(0),
            'total_amount' => $this->formatMoneyFromMinorUnits($totalMinorUnits),
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

    private function getExistingDetails(Transaction $transaction): Collection
    {
        return $transaction->transactionDetails()
            ->select('id', 'item_type', 'service_id', 'product_id', 'item_name', 'unit_price', 'qty', 'subtotal', 'commission_amount')
            ->orderBy('id')
            ->get();
    }

    private function mergeProductIdsForLock(Collection $existingDetails, array $productQtyById): array
    {
        return collect($this->extractProductIdsFromDetails($existingDetails))
            ->merge(array_keys($productQtyById))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function extractProductIdsFromDetails(Collection $existingDetails): array
    {
        return $existingDetails
            ->where('item_type', 'product')
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function lockProducts(array $productIds): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function assertStockAvailable(Product $product, int $qty): void
    {
        if ((int) $product->stock < $qty) {
            throw new DomainException(
                "Stok produk {$product->name} tidak cukup. Tersedia {$product->stock}, diminta {$qty}."
            );
        }
    }

    private function assertTransactionCanBeMutated(Transaction $transaction): void
    {
        if ($transaction->payroll_id === null) {
            return;
        }

        $payrollPeriod = $transaction->relationLoaded('payrollPeriod')
            ? $transaction->payrollPeriod
            : $transaction->payrollPeriod()->first(['id', 'status']);

        if ($payrollPeriod instanceof PayrollPeriod && $payrollPeriod->status === 'closed') {
            throw new DomainException(self::CLOSED_PAYROLL_MESSAGE);
        }
    }

    private function moneyToMinorUnits(string|int|null $amount): int
    {
        $normalized = trim((string) ($amount ?? '0'));

        if ($normalized === '') {
            return 0;
        }

        $isNegative = str_starts_with($normalized, '-');

        if ($isNegative) {
            $normalized = substr($normalized, 1);
        }

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new RuntimeException("Nilai uang tidak valid: {$amount}");
        }

        [$wholePart, $fractionPart] = array_pad(explode('.', $normalized, 2), 2, '0');
        $fractionPart = str_pad(substr($fractionPart, 0, self::MONEY_SCALE), self::MONEY_SCALE, '0');
        $minorUnits = ((int) $wholePart * self::MINOR_UNIT_MULTIPLIER) + (int) $fractionPart;

        return $isNegative ? -$minorUnits : $minorUnits;
    }

    private function formatMoneyFromMinorUnits(int $amount): string
    {
        $isNegative = $amount < 0;
        $absoluteAmount = abs($amount);
        $wholePart = intdiv($absoluteAmount, self::MINOR_UNIT_MULTIPLIER);
        $fractionPart = str_pad((string) ($absoluteAmount % self::MINOR_UNIT_MULTIPLIER), self::MONEY_SCALE, '0', STR_PAD_LEFT);

        return ($isNegative ? '-' : '').$wholePart.'.'.$fractionPart;
    }

    private function formatMoneyValue(string|int|null $amount): string
    {
        return $this->formatMoneyFromMinorUnits($this->moneyToMinorUnits($amount));
    }

    private function calculatePercentageMinorUnits(int $amount, int $basisPoints): int
    {
        return intdiv(($amount * $basisPoints) + 5000, 10000);
    }

    private function generateTransactionCode(string $transactionDate): string
    {
        return 'TRX-'.str_replace('-', '', $transactionDate).'-'.Str::upper((string) Str::ulid());
    }

    private function isTransactionCodeCollision(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? $exception->getCode();
        $message = Str::lower($exception->getMessage());

        return in_array((string) $sqlState, ['23000', '23505', '19'], true)
            && str_contains($message, 'transaction_code');
    }
}
