<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Support\Money;
use App\Support\Transactions\TransactionItemPayload;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransactionService
{
    private const MINIMUM_ITEM_MESSAGE = 'Transaksi harus berisi minimal 1 item.';
    private const CLOSED_PAYROLL_MESSAGE = 'Transaksi ini sudah masuk payroll final, jadi tidak bisa diedit atau dihapus.';
    private const TRANSACTION_CODE_RETRY_LIMIT = 5;

    public function __construct(
        private readonly CommissionRuleResolver $commissionRuleResolver,
    ) {
    }

    /**
     * Records a transaction snapshot directly for internal orchestration or tests.
     * The only supported user-facing entry flow remains daily batch input.
     */
    public function recordTransaction(array $validatedData): Transaction
    {
        $validatedData = $this->normalizeItemizedTransactionPayload($validatedData);

        return DB::transaction(fn (): Transaction => $this->persistNewTransaction($validatedData));
    }

    public function storeDailyBatch(array $validatedData): Collection
    {
        $validatedData = $this->normalizeDailyBatchPayload($validatedData);

        return DB::transaction(function () use ($validatedData): Collection {
            $transactions = collect();
            $batchProductQtyById = $this->extractBatchProductQuantities($validatedData['entries'] ?? []);
            $lockedBatchProducts = $this->lockProducts(array_keys($batchProductQtyById));

            $this->assertProductsExist($lockedBatchProducts, array_keys($batchProductQtyById));
            $this->assertStockAvailableForQuantities($lockedBatchProducts, $batchProductQtyById);

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

    /**
     * Rebuilds an existing transaction snapshot for internal maintenance logic.
     * This is not a public single-transaction UI API.
     */
    public function replaceTransaction(Transaction $transaction, array $validatedData): Transaction
    {
        $validatedData = $this->normalizeItemizedTransactionPayload($validatedData);

        return DB::transaction(function () use ($transaction, $validatedData): Transaction {
            $transaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->with('payrollPeriod:id,status')
                ->firstOrFail();

            $this->assertTransactionCanBeMutated($transaction);
            $this->syncTransactionItems($transaction, $validatedData, $this->getExistingItems($transaction));

            return $transaction->fresh(['transactionItems']);
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

    private function persistNewTransaction(array $validatedData): Transaction
    {
        $items = $this->extractItems($validatedData);
        $this->assertHasMinimumItems($items);

        $transaction = $this->createTransactionRecord($validatedData);
        $this->syncTransactionItems($transaction, $validatedData);

        return $transaction->fresh(['transactionItems']);
    }

    private function syncTransactionItems(
        Transaction $transaction,
        array $validatedData,
        ?Collection $existingItems = null
    ): void {
        $items = $this->extractItems($validatedData);
        $this->assertHasMinimumItems($items);

        $existingItems ??= collect();
        $productQtyById = $this->extractProductQuantitiesFromItems($items);
        $lockedProducts = $this->lockProducts($this->mergeProductIdsForLock($existingItems, $productQtyById));

        $this->assertProductsExist($lockedProducts, array_keys($productQtyById));
        $this->restoreOldProductStocks($existingItems, $lockedProducts);
        $this->assertStockAvailableForQuantities($lockedProducts, $productQtyById);

        $employees = $this->loadEmployeesForItems($items);
        $services = $this->loadServicesForItems($items);

        $transaction->transactionItems()->delete();
        $this->syncTransactionHeader($transaction, $validatedData);

        $total = Money::zero();

        foreach ($items as $item) {
            $detailAttributes = $this->buildTransactionItemAttributes(
                $item,
                $employees,
                $services,
                $lockedProducts
            );

            $transaction->transactionItems()->create($detailAttributes);
            $total = $total->add(Money::parse($detailAttributes['subtotal']));
        }

        $this->finalizeTransactionTotals($transaction, $total);
    }

    private function buildTransactionItemAttributes(
        array $item,
        Collection $employees,
        Collection $services,
        Collection $products
    ): array {
        $employeeId = (int) ($item['employee_id'] ?? 0);
        $employee = $employees->get($employeeId);

        if (! $employee instanceof Employee) {
            throw new DomainException("Pegawai dengan ID {$employeeId} tidak ditemukan.");
        }

        $commissionOverride = $this->extractCommissionOverride($item);

        return match ($item['item_type']) {
            'service' => $this->buildServiceItemAttributes($item, $employee, $services, $commissionOverride),
            'product' => $this->buildProductItemAttributes($item, $employee, $products, $commissionOverride),
            default => throw new DomainException('Tipe item transaksi tidak valid.'),
        };
    }

    private function buildServiceItemAttributes(
        array $item,
        Employee $employee,
        Collection $services,
        ?array $commissionOverride
    ): array {
        $serviceId = (int) ($item['service_id'] ?? 0);
        $qty = (int) ($item['qty'] ?? 0);
        $service = $services->get($serviceId);

        if (! $service instanceof Service) {
            throw new DomainException("Layanan dengan ID {$serviceId} tidak ditemukan.");
        }

        if ($qty !== 1) {
            throw new DomainException("Qty layanan {$service->name} harus 1.");
        }

        $unitPrice = Money::fromInput($service->price);
        $subtotal = $unitPrice;
        $commissionSnapshot = $this->commissionRuleResolver->resolveForService(
            $service,
            $subtotal->minorUnits(),
            $commissionOverride,
        );

        return [
            'item_type' => 'service',
            'service_id' => $service->id,
            'product_id' => null,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'employee_employment_type' => $this->resolveEmployeeEmploymentType($employee),
            'item_name' => $service->name,
            'unit_price' => $unitPrice->toDecimal(),
            'qty' => 1,
            'subtotal' => $subtotal->toDecimal(),
            'commission_source' => $commissionSnapshot['commission_source'],
            'commission_type' => $commissionSnapshot['commission_type'],
            'commission_value' => $commissionSnapshot['commission_value'],
            'commission_amount' => $commissionSnapshot['commission_amount'],
        ];
    }

    private function buildProductItemAttributes(
        array $item,
        Employee $employee,
        Collection $products,
        ?array $commissionOverride
    ): array {
        $productId = (int) ($item['product_id'] ?? 0);
        $qty = (int) ($item['qty'] ?? 0);
        $product = $products->get($productId);

        if (! $product instanceof Product) {
            throw new DomainException("Produk dengan ID {$productId} tidak ditemukan.");
        }

        if ($qty < 1) {
            throw new DomainException("Qty produk {$product->name} minimal 1.");
        }

        $unitPrice = Money::fromInput($product->price);
        $subtotal = $unitPrice->multiplyByInteger($qty);
        $commissionSnapshot = $this->commissionRuleResolver->resolveForProduct(
            $product,
            $subtotal->minorUnits(),
            $qty,
            $commissionOverride,
        );

        $this->adjustProductStock($product, -$qty);

        return [
            'item_type' => 'product',
            'service_id' => null,
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'employee_employment_type' => $this->resolveEmployeeEmploymentType($employee),
            'item_name' => $product->name,
            'unit_price' => $unitPrice->toDecimal(),
            'qty' => $qty,
            'subtotal' => $subtotal->toDecimal(),
            'commission_source' => $commissionSnapshot['commission_source'],
            'commission_type' => $commissionSnapshot['commission_type'],
            'commission_value' => $commissionSnapshot['commission_value'],
            'commission_amount' => $commissionSnapshot['commission_amount'],
        ];
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

    private function syncTransactionHeader(Transaction $transaction, array $validatedData): void
    {
        $transaction->update($this->buildTransactionAttributes($validatedData));
    }

    private function buildTransactionAttributes(array $validatedData): array
    {
        $employeeId = (int) ($validatedData['employee_id'] ?? 0);

        if ($employeeId < 1) {
            throw new DomainException('Pegawai transaksi wajib dipilih.');
        }

        return [
            'transaction_date' => Carbon::parse($validatedData['transaction_date'])->toDateString(),
            'employee_id' => $employeeId,
            'payment_method' => $validatedData['payment_method'],
            'subtotal_amount' => Money::zero()->toDecimal(),
            'discount_amount' => Money::zero()->toDecimal(),
            'total_amount' => Money::zero()->toDecimal(),
            'notes' => TransactionItemPayload::normalizeOptionalText($validatedData['notes'] ?? null),
        ];
    }

    private function finalizeTransactionTotals(Transaction $transaction, Money $total): void
    {
        $transaction->update([
            'subtotal_amount' => $total->toDecimal(),
            'discount_amount' => Money::zero()->toDecimal(),
            'total_amount' => $total->toDecimal(),
        ]);
    }

    private function loadEmployeesForItems(array $items): Collection
    {
        $employeeIds = collect($items)
            ->pluck('employee_id')
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return Employee::query()
            ->whereIn('id', $employeeIds)
            ->get()
            ->keyBy('id');
    }

    private function loadServicesForItems(array $items): Collection
    {
        $serviceIds = collect($items)
            ->where('item_type', 'service')
            ->pluck('service_id')
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return Service::query()
            ->whereIn('id', $serviceIds)
            ->get()
            ->keyBy('id');
    }

    private function normalizeItemizedTransactionPayload(array $validatedData): array
    {
        return TransactionItemPayload::normalizeItemizedTransactionPayload($validatedData);
    }

    private function normalizeDailyBatchPayload(array $validatedData): array
    {
        return [
            ...$validatedData,
            'entries' => collect($validatedData['entries'] ?? [])
                ->filter(fn ($entry) => is_array($entry))
                ->map(function (array $entry) use ($validatedData): array {
                    return $this->normalizeItemizedTransactionPayload([
                        ...$entry,
                        'employee_id' => $entry['employee_id'] ?? ($validatedData['employee_id'] ?? null),
                    ]);
                })
                ->values()
                ->all(),
        ];
    }

    private function mergeBatchEntryPayload(array $validatedData, array $entry): array
    {
        return $this->normalizeItemizedTransactionPayload([
            ...$entry,
            'transaction_date' => $validatedData['transaction_date'],
            'employee_id' => $entry['employee_id'] ?? ($validatedData['employee_id'] ?? null),
        ]);
    }

    private function extractItems(array $validatedData): array
    {
        return collect($validatedData['items'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->values()
            ->all();
    }

    private function assertHasMinimumItems(array $items): void
    {
        if ($items === []) {
            throw new DomainException(self::MINIMUM_ITEM_MESSAGE);
        }
    }

    private function extractCommissionOverride(array $item): ?array
    {
        $commissionType = $item['commission_type'] ?? null;
        $commissionValue = $item['commission_value'] ?? null;

        if (! filled($commissionType) && ! filled($commissionValue)) {
            return null;
        }

        if (! filled($commissionType) || ! filled($commissionValue)) {
            throw new DomainException('Override komisi item tidak lengkap.');
        }

        if ($commissionType === 'percent' && Money::parsePercentageToBasisPoints($commissionValue) > 10000) {
            throw new DomainException('Nilai komisi persen item harus berada di antara 0 sampai 100.');
        }

        if (Money::fromInput($commissionValue)->minorUnits() < 0) {
            throw new DomainException('Nilai komisi item tidak boleh negatif.');
        }

        return [
            'commission_type' => $commissionType,
            'commission_value' => Money::fromInput($commissionValue)->toDecimal(),
        ];
    }

    private function extractProductQuantitiesFromItems(array $items): array
    {
        return collect($items)
            ->where('item_type', 'product')
            ->map(fn (array $item) => [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'qty' => (int) ($item['qty'] ?? 0),
            ])
            ->filter(fn (array $row) => $row['product_id'] > 0 && $row['qty'] > 0)
            ->reduce(function (array $carry, array $row): array {
                $carry[$row['product_id']] = ($carry[$row['product_id']] ?? 0) + $row['qty'];

                return $carry;
            }, []);
    }

    private function extractBatchProductQuantities(array $entries): array
    {
        return collect($entries)
            ->flatMap(fn (array $entry) => $this->extractItems($entry))
            ->pipe(fn (Collection $items) => $this->extractProductQuantitiesFromItems($items->all()));
    }

    private function getExistingItems(Transaction $transaction): Collection
    {
        return $transaction->transactionItems()
            ->select('id', 'item_type', 'product_id', 'qty')
            ->orderBy('id')
            ->get();
    }

    private function restoreOldProductStocks(Collection $existingItems, Collection $lockedProducts): void
    {
        $restoreQtyByProductId = $existingItems
            ->where('item_type', 'product')
            ->filter(fn (TransactionItem $item) => $item->product_id !== null && (int) $item->qty > 0)
            ->groupBy('product_id')
            ->map(fn (Collection $rows) => (int) $rows->sum('qty'));

        foreach ($restoreQtyByProductId as $productId => $qtyToRestore) {
            $product = $lockedProducts->get((int) $productId);

            if ($product instanceof Product) {
                $this->adjustProductStock($product, $qtyToRestore);
            }
        }
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

    private function assertProductsExist(Collection $lockedProducts, array $productIds): void
    {
        foreach ($productIds as $productId) {
            if (! $lockedProducts->has((int) $productId)) {
                throw new DomainException("Produk dengan ID {$productId} tidak ditemukan.");
            }
        }
    }

    private function assertStockAvailableForQuantities(Collection $lockedProducts, array $productQtyById): void
    {
        foreach ($productQtyById as $productId => $qty) {
            $product = $lockedProducts->get((int) $productId);

            if (! $product instanceof Product) {
                throw new DomainException("Produk dengan ID {$productId} tidak ditemukan.");
            }

            if ((int) $product->stock < $qty) {
                throw new DomainException(
                    "Stok produk {$product->name} tidak cukup. Tersedia {$product->stock}, diminta {$qty}."
                );
            }
        }
    }

    private function adjustProductStock(Product $product, int $deltaQty): void
    {
        $newStock = (int) $product->stock + $deltaQty;

        if ($newStock < 0) {
            throw new DomainException("Stok produk {$product->name} tidak cukup.");
        }

        $product->stock = $newStock;
        $product->save();
    }

    private function assertTransactionCanBeMutated(Transaction $transaction): void
    {
        if ($transaction->payroll_id === null) {
            return;
        }

        $payrollPeriod = $transaction->relationLoaded('payrollPeriod')
            ? $transaction->payrollPeriod
            : $transaction->payrollPeriod()->first(['id', 'status']);

        if ($payrollPeriod instanceof PayrollPeriod && $payrollPeriod->status === PayrollPeriod::STATUS_CLOSED) {
            throw new DomainException(self::CLOSED_PAYROLL_MESSAGE);
        }
    }

    private function resolveEmployeeEmploymentType(Employee $employee): ?string
    {
        return $employee->employment_type ?: match ($employee->status) {
            'tetap' => Employee::EMPLOYMENT_TYPE_PERMANENT,
            'freelance' => Employee::EMPLOYMENT_TYPE_FREELANCE,
            default => null,
        };
    }

    private function generateTransactionCode(string $transactionDate): string
    {
        $tanggal = Carbon::parse($transactionDate)->toDateString();
        $formattedDate = Carbon::parse($transactionDate)->format('dmy');
        $prefix = 'TRX-'.$formattedDate.'-';

        $lastTransaction = Transaction::query()
            ->whereDate('transaction_date', $tanggal)
            ->where('transaction_code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('transaction_code')
            ->first(['transaction_code']);

        if (! $lastTransaction instanceof Transaction) {
            return $prefix.'0001';
        }

        $lastNumber = (int) substr((string) $lastTransaction->transaction_code, -4);
        $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);

        return $prefix.$nextNumber;
    }

    private function isTransactionCodeCollision(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? $exception->getCode();
        $message = \Illuminate\Support\Str::lower($exception->getMessage());

        return in_array((string) $sqlState, ['23000', '23505', '19'], true)
            && str_contains($message, 'transaction_code');
    }
}
