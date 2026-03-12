<?php

namespace Tests\Feature;

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
        $details = $transaction->transactionDetails()->orderBy('item_type')->orderBy('id')->get();
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
        $this->assertSame('25000.00', $serviceDetail?->commission_amount);

        $this->assertSame('Hair Clay', $productDetail?->item_name);
        $this->assertSame('75000.00', $productDetail?->unit_price);
        $this->assertSame(2, $productDetail?->qty);
        $this->assertSame('150000.00', $productDetail?->subtotal);
        $this->assertSame('10000.00', $productDetail?->commission_amount);
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

    public function test_update_transaction_restores_stock_and_refreshes_only_changed_item_snapshot(): void
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

        $service->update(['price' => '60000.00']);
        $product->update(['price' => '80000.00']);

        $serviceLayer->updateTransaction($transaction, [
            'transaction_date' => '2026-03-11',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'services' => [$service->id],
            'products' => [$product->id => 3],
        ]);

        $transaction->refresh();
        $details = $transaction->transactionDetails()->orderBy('item_type')->orderBy('id')->get();
        $productDetail = $details->firstWhere('item_type', 'product');
        $serviceDetail = $details->firstWhere('item_type', 'service');

        $this->assertSame('2026-03-11', $transaction->transaction_date?->toDateString());
        $this->assertSame('qr', $transaction->payment_method);
        $this->assertSame('290000.00', $transaction->total_amount);
        $this->assertSame(7, $product->fresh()->stock);

        $this->assertSame('50000.00', $serviceDetail?->unit_price);
        $this->assertSame('25000.00', $serviceDetail?->commission_amount);

        $this->assertSame('80000.00', $productDetail?->unit_price);
        $this->assertSame(3, $productDetail?->qty);
        $this->assertSame('240000.00', $productDetail?->subtotal);
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

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);
    }
}
