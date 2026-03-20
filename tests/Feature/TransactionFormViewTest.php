<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TransactionFormViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_module_registers_controlled_edit_routes_without_single_create_flow(): void
    {
        $this->assertTrue(Route::has('transactions.index'));
        $this->assertTrue(Route::has('transactions.show'));
        $this->assertTrue(Route::has('transactions.edit'));
        $this->assertTrue(Route::has('transactions.update'));
        $this->assertTrue(Route::has('transactions.destroy'));
        $this->assertTrue(Route::has('transactions.daily-batch.create'));
        $this->assertTrue(Route::has('transactions.daily-batch.store'));

        $this->assertFalse(Route::has('transactions.create'));
        $this->assertFalse(Route::has('transactions.store'));
    }

    public function test_transaction_index_shows_only_daily_batch_call_to_action(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('Input Harian');
        $response->assertSee(route('transactions.daily-batch.create'), false);
        $response->assertDontSee('Tambah Transaksi');
    }

    public function test_daily_batch_form_does_not_render_customer_field_and_only_lists_active_employees(): void
    {
        $user = User::factory()->create();
        Employee::query()->create([
            'name' => 'Pegawai Aktif',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PERMANENT,
            'is_active' => true,
        ]);
        Employee::query()->create([
            'name' => 'Pegawai Nonaktif',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PERMANENT,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get(route('transactions.daily-batch.create'));

        $response->assertOk();
        $response->assertSee('Input harian beberapa transaksi sekaligus');
        $response->assertSee('Pegawai Aktif');
        $response->assertDontSee('Pegawai Nonaktif');
        $response->assertDontSee('Nama Customer');
        $response->assertDontSee('Form Tunggal');
    }

    public function test_daily_batch_form_uses_item_scoped_names_and_assignable_item_master_models(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.daily-batch.create'));

        $response->assertOk();
        $response->assertSee('@click="addItem(entryIndex)"', false);
        $response->assertSee('entries[${entryIndex}][employee_id]', false);
        $response->assertSee('entries[${entryIndex}][items][${rowIndex}][item_type]', false);
        $response->assertSee('entries[${entryIndex}][items][${rowIndex}][service_id]', false);
        $response->assertSee('entries[${entryIndex}][items][${rowIndex}][product_id]', false);
        $response->assertSee('entries[${entryIndex}][items][${rowIndex}][qty]', false);
        $response->assertSee('x-model="item.service_id"', false);
        $response->assertSee('x-model="item.product_id"', false);
        $response->assertDontSee('x-model="item.item_type === \'service\' ? item.service_id : item.product_id"', false);
        $response->assertDontSee('entries[${entryIndex}][items][${rowIndex}][employee_id]', false);
        $response->assertDontSee('entries[${entryIndex}][items][${rowIndex}][commission_type]', false);
        $response->assertDontSee('entries[${entryIndex}][items][${rowIndex}][commission_value]', false);
        $response->assertDontSee('Pegawai Default Item');
        $response->assertSee('Pegawai Transaksi');
        $response->assertDontSee('Mode Komisi');
        $response->assertDontSee('Nilai Komisi');
        $response->assertSee('Qty layanan selalu 1 dan komisi mengikuti aturan layanan atau global.');
        $response->assertSee('Komisi produk mengikuti aturan produk atau global. Qty dapat diubah.');
    }

    public function test_transaction_show_page_shows_edit_button_for_unlocked_transaction(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);
        $transaction = Transaction::query()->create([
            'transaction_code' => 'TRX-TEST-003',
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '50000.00',
            'discount_amount' => '0.00',
            'total_amount' => '50000.00',
            'notes' => 'Audit only',
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'item_type' => 'service',
            'service_id' => $service->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'employee_employment_type' => $employee->employment_type,
            'item_name' => $service->name,
            'unit_price' => '50000.00',
            'qty' => 1,
            'subtotal' => '50000.00',
            'commission_source' => 'default',
            'commission_type' => 'percent',
            'commission_value' => '50.00',
            'commission_amount' => '25000.00',
        ]);

        $response = $this->actingAs($user)->get(route('transactions.show', $transaction));

        $response->assertOk();
        $response->assertSee('Dokumen Audit');
        $response->assertSee('Edit');
        $response->assertSee('Hapus');
        $response->assertSee('Audit only', false);
    }

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
            'is_active' => true,
        ]);
    }
}
