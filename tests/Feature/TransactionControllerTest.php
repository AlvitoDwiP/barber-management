<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\Reports\DailyReportService;
use App\Services\PayrollService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_edit_form_for_unlocked_transaction(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'notes' => 'Catatan awal',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $response = $this->actingAs($user)->get(route('transactions.edit', $transaction));

        $response->assertOk();
        $response->assertSee('Edit Transaksi');
        $response->assertSee($transaction->transaction_code);
        $response->assertSee('Catatan awal');
        $response->assertSee('Haircut');
        $response->assertSee(route('transactions.update', $transaction), false);
    }

    public function test_user_can_update_basic_service_transaction(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);
        $coloring = Service::query()->create([
            'name' => 'Coloring',
            'price' => '120000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'notes' => 'Input awal',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'transaction_date' => '2026-03-11',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'notes' => 'Koreksi owner',
            'items' => [
                [
                    'item_type' => 'service',
                    'service_id' => $coloring->id,
                    'employee_id' => $employee->id,
                    'qty' => 1,
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $response->assertSessionHas('success');

        $transaction->refresh();
        $detail = $transaction->transactionItems()->firstOrFail();

        $this->assertSame('2026-03-11', $transaction->transaction_date?->toDateString());
        $this->assertSame('qr', $transaction->payment_method);
        $this->assertSame('Koreksi owner', $transaction->notes);
        $this->assertSame('120000.00', $transaction->total_amount);
        $this->assertSame('Coloring', $detail->item_name);
        $this->assertSame('120000.00', $detail->unit_price);
    }

    public function test_user_can_update_product_transaction_and_stock_stays_correct(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '60000.00',
            'stock' => 10,
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [], [$product->id => 2]),
        ]);

        $this->assertSame(8, $product->fresh()->stock);

        $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'notes' => null,
            'items' => [
                [
                    'item_type' => 'product',
                    'product_id' => $product->id,
                    'employee_id' => $employee->id,
                    'qty' => 4,
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $this->assertSame(6, $product->fresh()->stock);
        $this->assertSame('240000.00', $transaction->fresh()->total_amount);
    }

    public function test_user_can_swap_product_items_and_stock_is_restored_then_reapplied(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $pomade = Product::query()->create([
            'name' => 'Pomade',
            'price' => '60000.00',
            'stock' => 10,
        ]);
        $spray = Product::query()->create([
            'name' => 'Spray',
            'price' => '40000.00',
            'stock' => 10,
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [], [$pomade->id => 3]),
        ]);

        $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'notes' => 'Ganti item',
            'items' => [
                [
                    'item_type' => 'product',
                    'product_id' => $spray->id,
                    'employee_id' => $employee->id,
                    'qty' => 2,
                ],
            ],
        ]);

        $response->assertRedirect(route('transactions.show', $transaction));
        $transaction->refresh();

        $this->assertSame(10, $pomade->fresh()->stock);
        $this->assertSame(8, $spray->fresh()->stock);
        $this->assertSame('80000.00', $transaction->total_amount);
        $this->assertSame('Spray', $transaction->transactionItems()->firstOrFail()->item_name);
    }

    public function test_reports_stay_in_sync_after_transaction_update(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
            'commission_type' => 'percent',
            'commission_value' => '50.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '20000.00',
            'stock' => 10,
            'commission_type' => 'fixed',
            'commission_value' => '5000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 1]),
        ]);

        $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'transaction_date' => '2026-03-11',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'notes' => 'Pindah tanggal',
            'items' => [
                [
                    'item_type' => 'service',
                    'service_id' => $service->id,
                    'employee_id' => $employee->id,
                    'qty' => 1,
                ],
                [
                    'item_type' => 'product',
                    'product_id' => $product->id,
                    'employee_id' => $employee->id,
                    'qty' => 2,
                ],
            ],
        ])->assertRedirect(route('transactions.show', $transaction));

        $report = app(DailyReportService::class)->getDailyReport('2026-03-10', '2026-03-11');
        $rows = $report['rows']->keyBy('report_date');

        $this->assertFalse($rows->has('2026-03-10'));
        $this->assertSame('90000.00', $rows->get('2026-03-11')['total_revenue']);
        $this->assertSame('35000.00', $rows->get('2026-03-11')['barber_commissions']);
        $this->assertSame('90000.00', $rows->get('2026-03-11')['cash_in']);
        $this->assertSame('55000.00', $rows->get('2026-03-11')['operating_profit']);
    }

    public function test_update_does_not_mutate_other_transaction_snapshots(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);

        $firstTransaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $secondTransaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $service->update([
            'price' => '80000.00',
            'commission_type' => 'percent',
            'commission_value' => '40.00',
        ]);

        $this->actingAs($user)->put(route('transactions.update', $firstTransaction), [
            'transaction_date' => '2026-03-11',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'notes' => 'Koreksi pertama',
            'items' => [
                [
                    'item_type' => 'service',
                    'service_id' => $service->id,
                    'employee_id' => $employee->id,
                    'qty' => 1,
                ],
            ],
        ])->assertRedirect(route('transactions.show', $firstTransaction));

        $this->assertSame('80000.00', $firstTransaction->fresh()->transactionItems()->firstOrFail()->unit_price);
        $this->assertSame('50000.00', $secondTransaction->fresh()->transactionItems()->firstOrFail()->unit_price);
    }

    public function test_authenticated_user_can_delete_unlocked_transaction(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $service = Service::query()->create([
            'name' => 'Coloring',
            'price' => '120000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $response = $this->actingAs($user)
            ->from(route('transactions.show', $transaction))
            ->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_destroy_transaction_blocks_closed_payroll_transaction(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $service = Service::query()->create([
            'name' => 'Coloring',
            'price' => '120000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $response = $this->actingAs($user)
            ->from(route('transactions.show', $transaction))
            ->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('transactions.show', $transaction));
        $response->assertSessionHas('error', 'Transaksi yang sudah terikat ke payroll tertutup tidak dapat diubah atau dihapus.');
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_show_transaction_page_displays_closed_payroll_notice(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $response = $this->actingAs($user)->get(route('transactions.show', $transaction));

        $response->assertOk();
        $response->assertSee('Transaksi terkunci payroll');
        $response->assertDontSee('Edit');
    }

    public function test_edit_and_update_routes_block_closed_payroll_transaction(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee('Budi');
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $this->actingAs($user)
            ->get(route('transactions.edit', $transaction))
            ->assertRedirect(route('transactions.show', $transaction))
            ->assertSessionHas('error', 'Transaksi yang sudah terikat ke payroll tertutup tidak dapat diubah atau dihapus.');

        $this->actingAs($user)
            ->from(route('transactions.show', $transaction))
            ->put(route('transactions.update', $transaction), [
                'transaction_date' => '2026-03-11',
                'employee_id' => $employee->id,
                'payment_method' => 'qr',
                'notes' => 'Tidak boleh tersimpan',
                'items' => [
                    [
                        'item_type' => 'service',
                        'service_id' => $service->id,
                        'employee_id' => $employee->id,
                        'qty' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('transactions.show', $transaction))
            ->assertSessionHas('error', 'Transaksi yang sudah terikat ke payroll tertutup tidak dapat diubah atau dihapus.');
    }

    private function createEmployee(string $name): Employee
    {
        return Employee::query()->create([
            'name' => $name,
            'employment_type' => Employee::EMPLOYMENT_TYPE_PERMANENT,
            'status' => 'tetap',
            'is_active' => true,
        ]);
    }
}
