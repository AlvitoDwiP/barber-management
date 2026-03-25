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

    public function test_record_transaction_persists_business_date_snapshots_and_stock(): void
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

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 2]),
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
        $this->assertSame($employee->id, $serviceDetail?->employee_id);
        $this->assertSame('Budi', $serviceDetail?->employee_name);
        $this->assertSame(Employee::EMPLOYMENT_TYPE_PERMANENT, $serviceDetail?->employee_employment_type);
        $this->assertSame('50000.00', $serviceDetail?->unit_price);
        $this->assertSame(1, $serviceDetail?->qty);
        $this->assertSame('50000.00', $serviceDetail?->subtotal);
        $this->assertSame('default', $serviceDetail?->commission_source);
        $this->assertSame('percent', $serviceDetail?->commission_type);
        $this->assertSame('50.00', $serviceDetail?->commission_value);
        $this->assertSame('25000.00', $serviceDetail?->commission_amount);

        $this->assertSame('Hair Clay', $productDetail?->item_name);
        $this->assertSame($employee->id, $productDetail?->employee_id);
        $this->assertSame('Budi', $productDetail?->employee_name);
        $this->assertSame(Employee::EMPLOYMENT_TYPE_PERMANENT, $productDetail?->employee_employment_type);
        $this->assertSame('75000.00', $productDetail?->unit_price);
        $this->assertSame(2, $productDetail?->qty);
        $this->assertSame('150000.00', $productDetail?->subtotal);
        $this->assertSame('default', $productDetail?->commission_source);
        $this->assertSame('fixed', $productDetail?->commission_type);
        $this->assertSame('5000.00', $productDetail?->commission_value);
        $this->assertSame('10000.00', $productDetail?->commission_amount);
    }

    public function test_record_transaction_supports_structured_line_items_and_optional_fields(): void
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

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-14',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'notes' => 'Pelanggan langganan',
            'items' => $this->transactionItems(
                $employee->id,
                [
                    ['service_id' => $service->id],
                    ['service_id' => $service->id],
                ],
                [
                    ['product_id' => $product->id, 'qty' => 1],
                ],
            ),
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

    public function test_record_transaction_stores_single_service_item_snapshot_correctly(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Beard Trim',
            'price' => '30000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-14',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $this->assertSame('30000.00', $transaction->fresh()->total_amount);
        $this->assertDatabaseCount('transaction_items', 1);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transaction->id,
            'item_type' => 'service',
            'service_id' => $service->id,
            'qty' => 1,
        ]);
    }

    public function test_record_transaction_stores_single_product_item_snapshot_correctly(): void
    {
        $employee = $this->createEmployee();
        $product = Product::query()->create([
            'name' => 'Sea Salt Spray',
            'price' => '45000.00',
            'stock' => 5,
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-14',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'items' => $this->transactionItems($employee->id, [], [$product->id => 1]),
        ]);

        $this->assertSame('45000.00', $transaction->fresh()->total_amount);
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transaction->id,
            'item_type' => 'product',
            'product_id' => $product->id,
            'qty' => 1,
        ]);
    }

    public function test_record_transaction_uses_current_global_default_commission_rules_when_master_has_no_override(): void
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

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 2]),
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

    public function test_record_transaction_uses_master_override_commission_rules_when_available(): void
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

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 2]),
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

    public function test_record_transaction_uses_fixed_product_override_per_quantity(): void
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
            'commission_type' => 'fixed',
            'commission_value' => '7000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 3]),
        ]);

        $productDetail = $transaction->transactionItems()
            ->where('item_type', 'product')
            ->first();

        $this->assertSame('override', $productDetail?->commission_source);
        $this->assertSame('fixed', $productDetail?->commission_type);
        $this->assertSame('7000.00', $productDetail?->commission_value);
        $this->assertSame('21000.00', $productDetail?->commission_amount);
    }

    public function test_record_transaction_preserves_exact_decimal_commissions_for_sensitive_percentages_and_minimal_fixed_fees(): void
    {
        $employee = $this->createEmployee();
        $trim = Service::query()->create([
            'name' => 'Trim Detail',
            'price' => '1000.00',
            'commission_type' => 'percent',
            'commission_value' => '33.33',
        ]);
        $color = Service::query()->create([
            'name' => 'Color Correction',
            'price' => '1000.00',
            'commission_type' => 'percent',
            'commission_value' => '66.67',
        ]);
        $sample = Product::query()->create([
            'name' => 'Ampoule Sample',
            'price' => '100.00',
            'stock' => 10,
            'commission_type' => 'fixed',
            'commission_value' => '0.01',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-18',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$trim->id, $color->id], [$sample->id => 3]),
        ]);

        $transaction->refresh();
        $details = $transaction->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $trimDetail = $details->firstWhere('service_id', $trim->id);
        $colorDetail = $details->firstWhere('service_id', $color->id);
        $sampleDetail = $details->firstWhere('product_id', $sample->id);

        $this->assertSame('2300.00', $transaction->subtotal_amount);
        $this->assertSame('2300.00', $transaction->total_amount);

        $this->assertSame('33.33', $trimDetail?->commission_value);
        $this->assertSame('333.30', $trimDetail?->commission_amount);

        $this->assertSame('66.67', $colorDetail?->commission_value);
        $this->assertSame('666.70', $colorDetail?->commission_amount);

        $this->assertSame('0.01', $sampleDetail?->commission_value);
        $this->assertSame('300.00', $sampleDetail?->subtotal);
        $this->assertSame('0.03', $sampleDetail?->commission_amount);
    }

    public function test_record_transaction_supports_custom_percent_and_fixed_commission_per_item(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Scalp Treatment',
            'price' => '80000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Hair Serum',
            'price' => '50000.00',
            'stock' => 10,
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-18',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'item_type' => 'service',
                    'service_id' => $service->id,
                    'employee_id' => $employee->id,
                    'qty' => 1,
                    'commission_type' => 'percent',
                    'commission_value' => '25.00',
                ],
                [
                    'item_type' => 'product',
                    'product_id' => $product->id,
                    'employee_id' => $employee->id,
                    'qty' => 2,
                    'commission_type' => 'fixed',
                    'commission_value' => '7500.00',
                ],
            ],
        ]);

        $details = $transaction->fresh()->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $productDetail = $details->firstWhere('item_type', 'product');
        $serviceDetail = $details->firstWhere('item_type', 'service');

        $this->assertSame('override', $serviceDetail?->commission_source);
        $this->assertSame('percent', $serviceDetail?->commission_type);
        $this->assertSame('25.00', $serviceDetail?->commission_value);
        $this->assertSame('20000.00', $serviceDetail?->commission_amount);

        $this->assertSame('override', $productDetail?->commission_source);
        $this->assertSame('fixed', $productDetail?->commission_type);
        $this->assertSame('7500.00', $productDetail?->commission_value);
        $this->assertSame('15000.00', $productDetail?->commission_amount);
    }

    public function test_existing_transaction_snapshot_does_not_change_after_default_settings_change(): void
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

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 2]),
        ]);

        CommissionSetting::query()->update([
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '10.00',
            'default_product_commission_type' => 'percent',
            'default_product_commission_value' => '2.00',
        ]);

        $details = $transaction->fresh()->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $productDetail = $details->firstWhere('item_type', 'product');
        $serviceDetail = $details->firstWhere('item_type', 'service');

        $this->assertSame('default', $serviceDetail?->commission_source);
        $this->assertSame('percent', $serviceDetail?->commission_type);
        $this->assertSame('50.00', $serviceDetail?->commission_value);
        $this->assertSame('25000.00', $serviceDetail?->commission_amount);

        $this->assertSame('default', $productDetail?->commission_source);
        $this->assertSame('fixed', $productDetail?->commission_type);
        $this->assertSame('5000.00', $productDetail?->commission_value);
        $this->assertSame('10000.00', $productDetail?->commission_amount);
    }

    public function test_existing_transaction_snapshot_does_not_change_after_master_override_change(): void
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
            'commission_type' => 'fixed',
            'commission_value' => '7000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 2]),
        ]);

        $service->update([
            'commission_type' => 'percent',
            'commission_value' => '10.00',
        ]);
        $product->update([
            'commission_type' => 'percent',
            'commission_value' => '1.00',
        ]);

        $details = $transaction->fresh()->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $productDetail = $details->firstWhere('item_type', 'product');
        $serviceDetail = $details->firstWhere('item_type', 'service');

        $this->assertSame('override', $serviceDetail?->commission_source);
        $this->assertSame('percent', $serviceDetail?->commission_type);
        $this->assertSame('40.00', $serviceDetail?->commission_value);
        $this->assertSame('20000.00', $serviceDetail?->commission_amount);

        $this->assertSame('override', $productDetail?->commission_source);
        $this->assertSame('fixed', $productDetail?->commission_type);
        $this->assertSame('7000.00', $productDetail?->commission_value);
        $this->assertSame('14000.00', $productDetail?->commission_amount);
    }

    public function test_existing_transaction_snapshot_does_not_change_after_master_price_change(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Signature Cut',
            'price' => '90000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Grooming Tonic',
            'price' => '25000.00',
            'stock' => 10,
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 2]),
        ]);

        $service->update(['price' => '120000.00']);
        $product->update(['price' => '40000.00']);

        $details = $transaction->fresh()->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $productDetail = $details->firstWhere('item_type', 'product');
        $serviceDetail = $details->firstWhere('item_type', 'service');

        $this->assertSame('90000.00', $serviceDetail?->unit_price);
        $this->assertSame('90000.00', $serviceDetail?->subtotal);
        $this->assertSame('25000.00', $productDetail?->unit_price);
        $this->assertSame('50000.00', $productDetail?->subtotal);
    }

    public function test_record_transaction_rolls_back_when_stock_is_insufficient(): void
    {
        $employee = $this->createEmployee();
        $product = Product::query()->create([
            'name' => 'Hair Powder',
            'price' => '70000.00',
            'stock' => 1,
        ]);

        try {
            app(TransactionService::class)->recordTransaction([
                'transaction_date' => '2026-03-12',
                'employee_id' => $employee->id,
                'payment_method' => 'cash',
                'items' => $this->transactionItems($employee->id, [], [$product->id => 2]),
            ]);

            $this->fail('Expected stock validation to fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Stok produk', $exception->getMessage());
        }

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_items', 0);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_replace_transaction_rebuilds_commission_snapshots_from_current_master_rules(): void
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
        $transaction = $serviceLayer->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 2]),
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

        $serviceLayer->replaceTransaction($transaction, [
            'transaction_date' => '2026-03-11',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 2]),
        ]);

        $transaction->refresh();
        $details = $transaction->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $productDetail = $details->firstWhere('item_type', 'product');
        $serviceDetail = $details->firstWhere('item_type', 'service');

        $this->assertSame('2026-03-11', $transaction->transaction_date?->toDateString());
        $this->assertSame('qr', $transaction->payment_method);
        $this->assertSame('220000.00', $transaction->total_amount);
        $this->assertSame(8, $product->fresh()->stock);

        $this->assertSame('60000.00', $serviceDetail?->unit_price);
        $this->assertSame('60000.00', $serviceDetail?->subtotal);
        $this->assertSame('override', $serviceDetail?->commission_source);
        $this->assertSame('percent', $serviceDetail?->commission_type);
        $this->assertSame('40.00', $serviceDetail?->commission_value);
        $this->assertSame('24000.00', $serviceDetail?->commission_amount);

        $this->assertSame('80000.00', $productDetail?->unit_price);
        $this->assertSame(2, $productDetail?->qty);
        $this->assertSame('160000.00', $productDetail?->subtotal);
        $this->assertSame('override', $productDetail?->commission_source);
        $this->assertSame('percent', $productDetail?->commission_type);
        $this->assertSame('10.00', $productDetail?->commission_value);
        $this->assertSame('16000.00', $productDetail?->commission_amount);
    }

    public function test_replace_transaction_restores_and_reduces_stock_safely_when_product_mix_changes(): void
    {
        $employee = $this->createEmployee();
        $pomade = Product::query()->create([
            'name' => 'Pomade',
            'price' => '60000.00',
            'stock' => 10,
        ]);
        $spray = Product::query()->create([
            'name' => 'Finishing Spray',
            'price' => '40000.00',
            'stock' => 10,
        ]);

        $service = app(TransactionService::class);
        $transaction = $service->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [], [$pomade->id => 3]),
        ]);

        $this->assertSame(7, $pomade->fresh()->stock);
        $this->assertSame(10, $spray->fresh()->stock);

        $service->replaceTransaction($transaction, [
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'item_type' => 'product',
                    'product_id' => $pomade->id,
                    'employee_id' => $employee->id,
                    'qty' => 1,
                ],
                [
                    'item_type' => 'product',
                    'product_id' => $spray->id,
                    'employee_id' => $employee->id,
                    'qty' => 2,
                ],
            ],
        ]);

        $this->assertSame(9, $pomade->fresh()->stock);
        $this->assertSame(8, $spray->fresh()->stock);
        $this->assertSame('140000.00', $transaction->fresh()->total_amount);
    }

    public function test_record_transaction_supports_multi_item_with_different_employees(): void
    {
        $permanentEmployee = $this->createEmployee();
        $freelanceEmployee = Employee::query()->create([
            'name' => 'Sari',
            'employment_type' => Employee::EMPLOYMENT_TYPE_FREELANCE,
            'status' => 'freelance',
            'is_active' => true,
        ]);
        $haircut = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);
        $wash = Service::query()->create([
            'name' => 'Hair Wash',
            'price' => '30000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-18',
            'employee_id' => $permanentEmployee->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'item_type' => 'service',
                    'service_id' => $haircut->id,
                    'employee_id' => $permanentEmployee->id,
                    'qty' => 1,
                ],
                [
                    'item_type' => 'service',
                    'service_id' => $wash->id,
                    'employee_id' => $freelanceEmployee->id,
                    'qty' => 1,
                ],
            ],
        ]);

        $details = $transaction->fresh()->transactionItems()->orderBy('id')->get();

        $this->assertSame('80000.00', $transaction->total_amount);
        $this->assertSame($permanentEmployee->id, $details[0]->employee_id);
        $this->assertSame(Employee::EMPLOYMENT_TYPE_PERMANENT, $details[0]->employee_employment_type);
        $this->assertSame($freelanceEmployee->id, $details[1]->employee_id);
        $this->assertSame(Employee::EMPLOYMENT_TYPE_FREELANCE, $details[1]->employee_employment_type);
    }

    public function test_delete_transaction_restores_product_stock(): void
    {
        $employee = $this->createEmployee();
        $product = Product::query()->create([
            'name' => 'Minuman',
            'price' => '10000.00',
            'stock' => 5,
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [], [$product->id => 3]),
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

        $firstTransaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $secondTransaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $this->assertNotSame($firstTransaction->transaction_code, $secondTransaction->transaction_code);
        $this->assertSame('TRX-120326-0001', $firstTransaction->transaction_code);
        $this->assertSame('TRX-120326-0002', $secondTransaction->transaction_code);
        $this->assertSame(2, Transaction::query()->count());
    }

    public function test_generated_transaction_codes_reset_for_a_new_transaction_date(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Creambath',
            'price' => '85000.00',
        ]);

        $firstTransaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $secondTransaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-13',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $this->assertSame('TRX-120326-0001', $firstTransaction->transaction_code);
        $this->assertSame('TRX-130326-0001', $secondTransaction->transaction_code);
    }

    public function test_generated_transaction_codes_ignore_legacy_long_codes_on_the_same_day(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Hair Spa',
            'price' => '90000.00',
        ]);

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260312-01KMG89F3RPDB1AVNJFYQ9QSZ8',
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '0.00',
            'discount_amount' => '0.00',
            'total_amount' => '0.00',
            'notes' => 'Legacy transaction code',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $this->assertSame('TRX-120326-0001', $transaction->transaction_code);
    }

    public function test_store_daily_batch_creates_multiple_separate_transactions(): void
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
                    'items' => $this->transactionItems(
                        $employee->id,
                        [
                            ['service_id' => $serviceA->id],
                            ['service_id' => $serviceA->id],
                        ],
                        [
                            ['product_id' => $product->id, 'qty' => 1],
                        ],
                    ),
                ],
                [
                    'payment_method' => 'qr',
                    'notes' => null,
                    'items' => $this->transactionItems(
                        $employee->id,
                        [
                            ['service_id' => $serviceB->id],
                        ],
                    ),
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
                        'items' => $this->transactionItems(
                            $employee->id,
                            [
                                ['service_id' => $service->id],
                            ],
                            [
                                ['product_id' => $product->id, 'qty' => 1],
                            ],
                        ),
                    ],
                    [
                        'payment_method' => 'cash',
                        'notes' => null,
                        'items' => $this->transactionItems(
                            $employee->id,
                            [],
                            [
                                ['product_id' => $product->id, 'qty' => 1],
                            ],
                        ),
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
                        'items' => $this->transactionItems(
                            $employee->id,
                            [],
                            [
                                ['product_id' => $product->id, 'qty' => 2],
                            ],
                        ),
                    ],
                    [
                        'payment_method' => 'qr',
                        'notes' => null,
                        'items' => $this->transactionItems(
                            $employee->id,
                            [],
                            [
                                ['product_id' => $product->id, 'qty' => 2],
                            ],
                        ),
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
