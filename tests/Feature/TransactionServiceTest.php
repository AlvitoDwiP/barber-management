<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Services\TransactionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_transaction_persists_business_date_snapshots_and_stock(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Hair Clay',
            'price' => '75000.00',
            'stock' => 10,
        ]);

        $transaction = app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [$product->id => 2],
        ]);

        $transaction->refresh();
        $details = $transaction->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $productDetail = $details->firstWhere('item_type', 'product');
        $serviceDetail = $details->firstWhere('item_type', 'service');

        $this->assertNotNull($transaction->transaction_code);
        $this->assertSame('2026-03-12', $transaction->transaction_date?->toDateString());
        $this->assertSame('200000.00', $transaction->subtotal_amount);
        $this->assertSame('0.00', $transaction->discount_amount);
        $this->assertSame('200000.00', $transaction->total_amount);
        $this->assertSame(8, $product->fresh()->stock);

        $this->assertSame('Haircut', $serviceDetail?->item_name);
        $this->assertSame('50000.00', $serviceDetail?->unit_price);
        $this->assertSame(1, $serviceDetail?->qty);
        $this->assertSame('50000.00', $serviceDetail?->subtotal);
        $this->assertSame('default', $serviceDetail?->commission_source);
        $this->assertSame('percent', $serviceDetail?->commission_type);
        $this->assertSame('50.00', $serviceDetail?->commission_value);
        $this->assertSame('25000.00', $serviceDetail?->commission_amount);

        $this->assertSame('Hair Clay', $productDetail?->item_name);
        $this->assertSame('75000.00', $productDetail?->unit_price);
        $this->assertSame(2, $productDetail?->qty);
        $this->assertSame('150000.00', $productDetail?->subtotal);
        $this->assertSame('default', $productDetail?->commission_source);
        $this->assertSame('fixed', $productDetail?->commission_type);
        $this->assertSame('5000.00', $productDetail?->commission_value);
        $this->assertSame('10000.00', $productDetail?->commission_amount);
    }

    public function test_store_transaction_supports_structured_line_items_and_optional_fields(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Coloring',
            'price' => '120000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Serum',
            'price' => '40000.00',
            'stock' => 6,
        ]);

        $transaction = app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-14',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'notes' => 'Pelanggan langganan',
            'services' => [
                ['service_id' => $service->id],
                ['service_id' => $service->id],
            ],
            'products' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $transaction->refresh();
        $serviceDetails = $transaction->transactionItems()->where('item_type', 'service')->orderBy('id')->get();

        $this->assertSame('Pelanggan langganan', $transaction->notes);
        $this->assertSame('280000.00', $transaction->total_amount);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertCount(2, $serviceDetails);
        $this->assertTrue($serviceDetails->every(fn ($detail) => (int) $detail->qty === 1));
        $this->assertTrue($serviceDetails->every(fn ($detail) => $detail->subtotal === '120000.00'));
        $this->assertTrue($serviceDetails->every(fn ($detail) => $detail->commission_amount === '60000.00'));
    }

    public function test_store_transaction_uses_current_global_default_commission_rules_when_master_has_no_override(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Hair Tonic',
            'price' => '30000.00',
            'stock' => 10,
        ]);

        CommissionSetting::query()->update([
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '40.00',
            'default_product_commission_type' => 'percent',
            'default_product_commission_value' => '10.00',
        ]);

        $transaction = app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [$product->id => 2],
        ]);

        $details = $transaction->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $productDetail = $details->firstWhere('item_type', 'product');
        $serviceDetail = $details->firstWhere('item_type', 'service');

        $this->assertSame('default', $serviceDetail?->commission_source);
        $this->assertSame('percent', $serviceDetail?->commission_type);
        $this->assertSame('40.00', $serviceDetail?->commission_value);
        $this->assertSame('20000.00', $serviceDetail?->commission_amount);

        $this->assertSame('default', $productDetail?->commission_source);
        $this->assertSame('percent', $productDetail?->commission_type);
        $this->assertSame('10.00', $productDetail?->commission_value);
        $this->assertSame('6000.00', $productDetail?->commission_amount);
    }

    public function test_store_transaction_uses_master_override_commission_rules_when_available(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
            'commission_type' => 'percent',
            'commission_value' => '40.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Hair Clay',
            'price' => '75000.00',
            'stock' => 10,
            'commission_type' => 'percent',
            'commission_value' => '10.00',
        ]);

        $transaction = app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [$product->id => 2],
        ]);

        $details = $transaction->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $productDetail = $details->firstWhere('item_type', 'product');
        $serviceDetail = $details->firstWhere('item_type', 'service');

        $this->assertSame('override', $serviceDetail?->commission_source);
        $this->assertSame('percent', $serviceDetail?->commission_type);
        $this->assertSame('40.00', $serviceDetail?->commission_value);
        $this->assertSame('20000.00', $serviceDetail?->commission_amount);

        $this->assertSame('override', $productDetail?->commission_source);
        $this->assertSame('percent', $productDetail?->commission_type);
        $this->assertSame('10.00', $productDetail?->commission_value);
        $this->assertSame('15000.00', $productDetail?->commission_amount);
    }

    public function test_store_transaction_rolls_back_when_stock_is_insufficient(): void
    {
        $employee = $this->createEmployee();
        $product = Product::query()->create([
            'name' => 'Hair Powder',
            'price' => '70000.00',
            'stock' => 1,
        ]);

        try {
            app(TransactionService::class)->storeTransaction([
                'transaction_date' => '2026-03-12',
                'employee_id' => $employee->id,
                'payment_method' => 'cash',
                'services' => [],
                'products' => [$product->id => 2],
            ]);

            $this->fail('Expected stock validation to fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Stok produk', $exception->getMessage());
        }

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_update_transaction_rebuilds_commission_snapshots_from_current_master_rules(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Vitamin Rambut',
            'price' => '75000.00',
            'stock' => 10,
        ]);

        $serviceLayer = app(TransactionService::class);
        $transaction = $serviceLayer->storeTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [$product->id => 2],
        ]);

        $service->update([
            'price' => '60000.00',
            'commission_type' => 'percent',
            'commission_value' => '40.00',
        ]);
        $product->update([
            'price' => '80000.00',
            'commission_type' => 'percent',
            'commission_value' => '10.00',
        ]);

        $serviceLayer->updateTransaction($transaction, [
            'transaction_date' => '2026-03-11',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'services' => [$service->id],
            'products' => [$product->id => 2],
        ]);

        $transaction->refresh();
        $details = $transaction->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $productDetail = $details->firstWhere('item_type', 'product');
        $serviceDetail = $details->firstWhere('item_type', 'service');

        $this->assertSame('2026-03-11', $transaction->transaction_date?->toDateString());
        $this->assertSame('qr', $transaction->payment_method);
        $this->assertSame('200000.00', $transaction->total_amount);
        $this->assertSame(8, $product->fresh()->stock);

        $this->assertSame('50000.00', $serviceDetail?->unit_price);
        $this->assertSame('override', $serviceDetail?->commission_source);
        $this->assertSame('percent', $serviceDetail?->commission_type);
        $this->assertSame('40.00', $serviceDetail?->commission_value);
        $this->assertSame('20000.00', $serviceDetail?->commission_amount);

        $this->assertSame('75000.00', $productDetail?->unit_price);
        $this->assertSame(2, $productDetail?->qty);
        $this->assertSame('150000.00', $productDetail?->subtotal);
        $this->assertSame('override', $productDetail?->commission_source);
        $this->assertSame('percent', $productDetail?->commission_type);
        $this->assertSame('10.00', $productDetail?->commission_value);
        $this->assertSame('15000.00', $productDetail?->commission_amount);
    }

    public function test_delete_transaction_restores_product_stock(): void
    {
        $employee = $this->createEmployee();
        $product = Product::query()->create([
            'name' => 'Minuman',
            'price' => '10000.00',
            'stock' => 5,
        ]);

        $transaction = app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [],
            'products' => [$product->id => 3],
        ]);

        $this->assertSame(2, $product->fresh()->stock);

        app(TransactionService::class)->deleteTransaction($transaction);

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_generated_transaction_codes_are_unique(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Keratin',
            'price' => '450000.00',
        ]);

        $firstTransaction = app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [],
        ]);

        $secondTransaction = app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'services' => [$service->id],
            'products' => [],
        ]);

        $this->assertNotSame($firstTransaction->transaction_code, $secondTransaction->transaction_code);
        $this->assertSame(2, Transaction::query()->count());
    }

    public function test_store_daily_batch_creates_multiple_normal_transactions(): void
    {
        $employee = $this->createEmployee();
        $serviceA = Service::query()->create([
            'name' => 'Hair Spa',
            'price' => '90000.00',
        ]);
        $serviceB = Service::query()->create([
            'name' => 'Cuci Blow',
            'price' => '50000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Hair Tonic',
            'price' => '30000.00',
            'stock' => 10,
        ]);

        $transactions = app(TransactionService::class)->storeDailyBatch([
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'entries' => [
                [
                    'payment_method' => 'cash',
                    'notes' => 'Paket pagi',
                    'services' => [
                        ['service_id' => $serviceA->id],
                        ['service_id' => $serviceA->id],
                    ],
                    'products' => [
                        ['product_id' => $product->id, 'qty' => 1],
                    ],
                ],
                [
                    'payment_method' => 'qr',
                    'notes' => null,
                    'services' => [
                        ['service_id' => $serviceB->id],
                    ],
                    'products' => [],
                ],
            ],
        ]);

        $this->assertCount(2, $transactions);
        $this->assertDatabaseCount('transactions', 2);
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertSame('210000.00', $transactions[0]->fresh()->total_amount);
        $this->assertSame('50000.00', $transactions[1]->fresh()->total_amount);
    }

    public function test_store_daily_batch_rolls_back_all_transactions_when_one_entry_fails(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '60000.00',
            'stock' => 1,
        ]);

        try {
            app(TransactionService::class)->storeDailyBatch([
                'transaction_date' => '2026-03-15',
                'employee_id' => $employee->id,
                'entries' => [
                    [
                        'payment_method' => 'cash',
                        'notes' => null,
                        'services' => [
                            ['service_id' => $service->id],
                        ],
                        'products' => [
                            ['product_id' => $product->id, 'qty' => 1],
                        ],
                    ],
                    [
                        'payment_method' => 'cash',
                        'notes' => null,
                        'services' => [],
                        'products' => [
                            ['product_id' => $product->id, 'qty' => 1],
                        ],
                    ],
                ],
            ]);

            $this->fail('Expected batch transaction to fail when stock runs out.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Stok produk', $exception->getMessage());
        }

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_store_daily_batch_rejects_cumulative_product_qty_that_exceeds_stock(): void
    {
        $employee = $this->createEmployee();
        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '60000.00',
            'stock' => 3,
        ]);

        try {
            app(TransactionService::class)->storeDailyBatch([
                'transaction_date' => '2026-03-15',
                'employee_id' => $employee->id,
                'entries' => [
                    [
                        'payment_method' => 'cash',
                        'notes' => null,
                        'services' => [],
                        'products' => [
                            ['product_id' => $product->id, 'qty' => 2],
                        ],
                    ],
                    [
                        'payment_method' => 'qr',
                        'notes' => null,
                        'services' => [],
                        'products' => [
                            ['product_id' => $product->id, 'qty' => 2],
                        ],
                    ],
                ],
            ]);

            $this->fail('Expected cumulative stock validation to fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Stok produk', $exception->getMessage());
        }

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertSame(3, $product->fresh()->stock);
    }

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);
    }
}
