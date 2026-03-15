<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionFormViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_create_route_redirects_to_daily_batch_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $response->assertRedirect(route('transactions.daily-batch.create'));
    }

    public function test_transaction_index_only_shows_single_create_button_to_daily_batch(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('Tambah Transaksi');
        $response->assertDontSee('Input Transaksi Harian');
        $response->assertSee(route('transactions.daily-batch.create'), false);
        $response->assertDontSee(route('transactions.create'), false);
    }

    public function test_edit_form_keeps_existing_notes_without_rendering_object_string(): void
    {
        $user = User::factory()->create();
        $employee = $this->createEmployee();
        $transaction = Transaction::query()->create([
            'transaction_code' => 'TRX-TEST-001',
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '0.00',
            'discount_amount' => '0.00',
            'total_amount' => '0.00',
            'notes' => 'Catatan edit',
        ]);

        $response = $this->actingAs($user)->get(route('transactions.edit', $transaction));

        $response->assertOk();
        $response->assertDontSee('Nama Customer');
        $response->assertDontSee('[object HTMLTextAreaElement]');
        $response->assertSee('Catatan edit', false);
    }

    public function test_daily_batch_form_does_not_render_customer_field(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.daily-batch.create'));

        $response->assertOk();
        $response->assertDontSee('Nama Customer');
    }

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);
    }
}
