<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PayrollPeriod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class TransactionService
{
    private const MINIMUM_ITEM_MESSAGE = 'Transaksi harus berisi minimal 1 item: pilih minimal 1 layanan atau isi qty produk lebih dari 0.';
    private const CLOSED_PAYROLL_MESSAGE = 'Transaksi yang sudah terikat ke payroll tertutup tidak dapat diubah atau dihapus.';
    private const MONEY_SCALE = 2;
    private const MINOR_UNIT_MULTIPLIER = 100;
    private const TRANSACTION_CODE_RETRY_LIMIT = 5;

    public function __construct(
        private readonly CommissionRuleResolver $commissionRuleResolver,
    ) {
    }

    public function storeTransaction(array $validatedData): Transaction
    {
        return DB::transaction(fn (): Transaction => $this->persistNewTransaction($validatedData));
    }

    public function storeDailyBatch(array $validatedData): Collection
    {
        return DB::transaction(function () use ($validatedData): Collection {
            $transactions = collect();
            $batchProductQtyById = $this->extractBatchProductQuantities($validatedData['entries'] ?? []);
            $lockedBatchProducts = $this->lockProducts(array_keys($batchProductQtyById));

            $this->assertBatchStockAvailable($lockedBatchProducts, $batchProductQtyById);

            foreach ($validatedData['entries'] ?? [] as $index => $entry) {
                try {
                    $transactions->push(
                        $this->persistNewTransaction($this->mergeBatchEntryPayload($validatedData, $entry))
                    );
                } catch (DomainException $exception) {
                    throw new DomainException('Transaksi '.($index + 1).': '.$exception->getMessage(), previous: $exception);
                }
            }

            return $transactions;
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
            $this->syncTransactionItems($transaction, $validatedData, $this->getExistingItems($transaction));

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
            $existingItems = $this->getExistingItems($transaction);
            $lockedProducts = $this->lockProducts($this->extractProductIdsFromItems($existingItems));

            $this->restoreOldProductStocks($existingItems, $lockedProducts);

            $transaction->transactionItems()->delete();
            $transaction->delete();
        });
    }

    private function syncTransactionItems(
        Transaction $transaction,
        array $validatedData,
        ?Collection $existingItems = null
    ): void {
        [$serviceIds, $productQtyById] = $this->extractTransactionItems($validatedData);
        $this->assertHasMinimumItems($serviceIds, $productQtyById);

        $existingItems ??= collect();
        $lockedProducts = $this->lockProducts($this->mergeProductIdsForLock($existingItems, $productQtyById));

        $this->restoreOldProductStocks($existingItems, $lockedProducts);
        $transaction->transactionItems()->delete();
        $this->updateTransactionHeader($transaction, $validatedData);

        $totalMinorUnits = 0;
        $totalMinorUnits += $this->storeServiceItems($transaction, $serviceIds, $existingItems);
        $totalMinorUnits += $this->storeProductItems($transaction, $productQtyById, $existingItems, $lockedProducts);

        $this->finalizeTransactionTotals($transaction, $totalMinorUnits);
    }

    private function persistNewTransaction(array $validatedData): Transaction
    {
        [$serviceIds, $productQtyById] = $this->extractTransactionItems($validatedData);
        $this->assertHasMinimumItems($serviceIds, $productQtyById);

        $transaction = $this->createTransactionRecord($validatedData);
        $this->syncTransactionItems($transaction, $validatedData);

        return $transaction;
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
            'notes' => $this->normalizeOptionalText($validatedData['notes'] ?? null),
        ];
    }

    private function storeServiceItems(Transaction $transaction, array $serviceIds, Collection $existingItems): int
    {
        if ($serviceIds === []) {
            return 0;
        }

        $existingServiceDetailsById = $existingItems
            ->where('item_type', 'service')
            ->filter(fn (TransactionItem $detail) => $detail->service_id !== null)
            ->groupBy('service_id')
            ->map(fn (Collection $rows) => $rows->values());

        $services = Service::query()
            ->whereIn('id', array_values(array_unique($serviceIds)))
            ->get()
            ->keyBy('id');

        $totalMinorUnits = 0;

        foreach ($serviceIds as $serviceId) {
            $serviceDetailQueue = $existingServiceDetailsById->get($serviceId, collect());
            $existingDetail = $serviceDetailQueue->shift();
            $existingServiceDetailsById->put($serviceId, $serviceDetailQueue);
            $detailAttributes = $existingDetail instanceof TransactionItem && (int) $existingDetail->qty === 1
                ? $this->buildServiceDetailAttributes($services->get($serviceId), $serviceId, $existingDetail)
                : $this->buildServiceDetailAttributes($services->get($serviceId), $serviceId);

            $transaction->transactionItems()->create($detailAttributes);
            $totalMinorUnits += $this->moneyToMinorUnits($detailAttributes['subtotal']);
        }

        return $totalMinorUnits;
    }

    private function storeProductItems(
        Transaction $transaction,
        array $productQtyById,
        Collection $existingItems,
        Collection $lockedProducts
    ): int {
        if ($productQtyById === []) {
            return 0;
        }

        $existingProductDetails = $existingItems
            ->where('item_type', 'product')
            ->filter(fn (TransactionItem $detail) => $detail->product_id !== null)
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
                ? $this->buildProductDetailAttributes($product, $qty, $existingDetail)
                : $this->buildProductDetailAttributes($product, $qty);

            $currentStock = (int) $product->stock;
            $product->decrement('stock', $qty);
            $product->stock = max(0, $currentStock - $qty);

            $transaction->transactionItems()->create($detailAttributes);
            $totalMinorUnits += $this->moneyToMinorUnits($detailAttributes['subtotal']);
        }

        return $totalMinorUnits;
    }

    private function buildServiceDetailAttributes(
        ?Service $service,
        int $serviceId,
        ?TransactionItem $existingDetail = null
    ): array
    {
        if (! $service) {
            throw new DomainException("Layanan dengan ID {$serviceId} tidak ditemukan.");
        }

        $unitPrice = $existingDetail instanceof TransactionItem
            ? $this->formatMoneyValue($existingDetail->unit_price)
            : $this->formatMoneyValue($service->price);
        $unitPriceMinorUnits = $this->moneyToMinorUnits($unitPrice);
        $subtotalMinorUnits = $unitPriceMinorUnits;
        $commissionSnapshot = $this->commissionRuleResolver->resolveForService($service, $subtotalMinorUnits);

        return [
            'item_type' => 'service',
            'service_id' => $service->id,
            'product_id' => null,
            'item_name' => $existingDetail?->item_name ?? $service->name,
            'unit_price' => $this->formatMoneyFromMinorUnits($unitPriceMinorUnits),
            'qty' => 1,
            'subtotal' => $this->formatMoneyFromMinorUnits($subtotalMinorUnits),
            'commission_source' => $commissionSnapshot['commission_source'],
            'commission_type' => $commissionSnapshot['commission_type'],
            'commission_value' => $commissionSnapshot['commission_value'],
            'commission_amount' => $commissionSnapshot['commission_amount'],
        ];
    }

    private function buildProductDetailAttributes(
        Product $product,
        int $qty,
        ?TransactionItem $existingDetail = null
    ): array
    {
        $unitPrice = $existingDetail instanceof TransactionItem
            ? $this->formatMoneyValue($existingDetail->unit_price)
            : $this->formatMoneyValue($product->price);
        $unitPriceMinorUnits = $this->moneyToMinorUnits($unitPrice);
        $subtotalMinorUnits = $unitPriceMinorUnits * $qty;
        $commissionSnapshot = $this->commissionRuleResolver->resolveForProduct($product, $subtotalMinorUnits, $qty);

        return [
            'item_type' => 'product',
            'service_id' => null,
            'product_id' => $product->id,
            'item_name' => $existingDetail?->item_name ?? $product->name,
            'unit_price' => $this->formatMoneyFromMinorUnits($unitPriceMinorUnits),
            'qty' => $qty,
            'subtotal' => $this->formatMoneyFromMinorUnits($subtotalMinorUnits),
            'commission_source' => $commissionSnapshot['commission_source'],
            'commission_type' => $commissionSnapshot['commission_type'],
            'commission_value' => $commissionSnapshot['commission_value'],
            'commission_amount' => $commissionSnapshot['commission_amount'],
        ];
    }

    private function restoreOldProductStocks(Collection $existingItems, Collection $lockedProducts): void
    {
        $restoreQtyByProductId = $existingItems
            ->where('item_type', 'product')
            ->filter(fn (TransactionItem $detail) => $detail->product_id !== null && (int) $detail->qty > 0)
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
        $services = $validatedData['services'] ?? [];

        if (! is_array($services)) {
            return [];
        }

        return collect($services)
            ->map(function ($row) {
                if (is_array($row)) {
                    return (int) ($row['service_id'] ?? 0);
                }

                return (int) $row;
            })
            ->filter(fn ($serviceId) => $serviceId > 0)
            ->values()
            ->all();
    }

    private function extractProductQuantities(array $validatedData): array
    {
        $products = $validatedData['products'] ?? [];

        if (! is_array($products)) {
            return [];
        }

        return collect($products)
            ->map(function ($row, $productId) {
                if (is_array($row)) {
                    return [
                        'product_id' => (int) ($row['product_id'] ?? 0),
                        'qty' => (int) ($row['qty'] ?? 0),
                    ];
                }

                return [
                    'product_id' => (int) $productId,
                    'qty' => (int) $row,
                ];
            })
            ->filter(fn (array $row) => $row['product_id'] > 0 && $row['qty'] > 0)
            ->reduce(function (array $carry, array $row): array {
                $carry[$row['product_id']] = ($carry[$row['product_id']] ?? 0) + $row['qty'];

                return $carry;
            }, []);
    }

    private function mergeBatchEntryPayload(array $validatedData, array $entry): array
    {
        return [
            'transaction_date' => $validatedData['transaction_date'],
            'employee_id' => $validatedData['employee_id'],
            'payment_method' => $entry['payment_method'],
            'notes' => $entry['notes'] ?? null,
            'services' => $entry['services'] ?? [],
            'products' => $entry['products'] ?? [],
        ];
    }

    private function extractBatchProductQuantities(array $entries): array
    {
        return collect($entries)
            ->filter(fn ($entry) => is_array($entry))
            ->flatMap(function (array $entry) {
                $products = $entry['products'] ?? [];

                if (! is_array($products)) {
                    return [];
                }

                return collect($products)
                    ->map(fn ($row) => [
                        'product_id' => (int) ($row['product_id'] ?? 0),
                        'qty' => (int) ($row['qty'] ?? 0),
                    ]);
            })
            ->filter(fn (array $row) => $row['product_id'] > 0 && $row['qty'] > 0)
            ->reduce(function (array $carry, array $row): array {
                $carry[$row['product_id']] = ($carry[$row['product_id']] ?? 0) + $row['qty'];

                return $carry;
            }, []);
    }

    private function normalizeOptionalText(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private function getExistingItems(Transaction $transaction): Collection
    {
        return $transaction->transactionItems()
            ->select('id', 'item_type', 'service_id', 'product_id', 'item_name', 'unit_price', 'qty', 'subtotal', 'commission_amount')
            ->orderBy('id')
            ->get();
    }

    private function mergeProductIdsForLock(Collection $existingItems, array $productQtyById): array
    {
        return collect($this->extractProductIdsFromItems($existingItems))
            ->merge(array_keys($productQtyById))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function extractProductIdsFromItems(Collection $existingItems): array
    {
        return $existingItems
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

    private function assertBatchStockAvailable(Collection $lockedProducts, array $productQtyById): void
    {
        foreach ($productQtyById as $productId => $qty) {
            $product = $lockedProducts->get((int) $productId);

            if (! $product) {
                throw new DomainException("Produk dengan ID {$productId} tidak ditemukan.");
            }

            $this->assertStockAvailable($product, $qty);
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
