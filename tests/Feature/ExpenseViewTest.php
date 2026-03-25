<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\FreelancePayment;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_index_shows_operational_empty_state_and_cta(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSeeText('Catat dan cek pengeluaran dengan cepat');
        $response->assertSeeText('Pengeluaran Operasional');
        $response->assertSeeText('Laba Operasional');
        $response->assertSeeText('Belum ada pengeluaran tercatat.');
        $response->assertSeeText('Catat Pengeluaran');
    }

    public function test_expense_index_shows_amount_hierarchy_and_manual_actions(): void
    {
        $user = User::factory()->create();

        $expense = Expense::query()->create([
            'expense_date' => '2026-03-18',
            'category' => Expense::CATEGORY_ELECTRICITY,
            'amount' => '125000.00',
            'note' => 'Bayar listrik dan lampu depan',
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSeeText('Nominal pengeluaran');
        $response->assertSeeText('Daftar Pengeluaran');
        $response->assertSeeText('Bayar listrik dan lampu depan');
        $response->assertSeeText('Listrik');
        $response->assertSee(route('expenses.edit', $expense), false);
        $response->assertSee(route('expenses.destroy', $expense), false);
    }

    public function test_expense_create_and_edit_pages_show_operational_helper_copy(): void
    {
        $user = User::factory()->create();
        $expense = Expense::query()->create([
            'expense_date' => '2026-03-15',
            'category' => Expense::CATEGORY_OTHER,
            'amount' => '50000.00',
            'note' => 'Isi ulang galon',
        ]);

        $createResponse = $this->actingAs($user)->get(route('expenses.create'));

        $createResponse->assertOk();
        $createResponse->assertSeeText('Catat pengeluaran baru');
        $createResponse->assertSeeText('Input Pengeluaran');
        $createResponse->assertSeeText('Nominal Pengeluaran');
        $createResponse->assertSeeText('Pengeluaran Operasional');
        $createResponse->assertSeeText('Laba Operasional');

        $editResponse = $this->actingAs($user)->get(route('expenses.edit', $expense));

        $editResponse->assertOk();
        $editResponse->assertSeeText('Perbarui pengeluaran yang sudah tercatat');
        $editResponse->assertSeeText('Isi ulang galon');
        $editResponse->assertSeeText('Lainnya');
        $editResponse->assertSeeText('Simpan Perubahan');
    }

    public function test_expense_index_shows_payroll_label_for_freelance_expense_without_manual_actions(): void
    {
        $user = User::factory()->create();
        $employee = Employee::query()->create([
            'name' => 'Dafasand',
            'status' => 'freelance',
            'employment_type' => 'freelance',
            'is_active' => true,
        ]);
        $expense = Expense::query()->create([
            'expense_date' => '2026-03-19',
            'category' => Expense::CATEGORY_PAY_FREELANCE,
            'amount' => '175000.00',
            'note' => 'Pembayaran freelance',
        ]);

        FreelancePayment::query()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-19',
            'total_service_amount' => '350000.00',
            'service_commission' => '150000.00',
            'total_product_qty' => 1,
            'product_commission' => '25000.00',
            'total_commission' => '175000.00',
            'expense_id' => $expense->id,
            'payment_status' => FreelancePayment::STATUS_PAID,
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSeeText('Bayar Freelance');
        $response->assertSeeText('Dikelola via Payroll');
        $response->assertDontSee(route('expenses.edit', $expense), false);
        $response->assertDontSee(route('expenses.destroy', $expense), false);
    }

    public function test_expense_store_uses_clear_validation_messages(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('expenses.create'))
            ->post(route('expenses.store'), [
                'expense_date' => '',
                'category' => '',
                'amount' => '',
                'note' => ['invalid'],
            ]);

        $response->assertRedirect(route('expenses.create'));
        $response->assertSessionHasErrors([
            'expense_date' => 'Tanggal pengeluaran wajib diisi.',
            'category' => 'Pilih kategori pengeluaran.',
            'amount' => 'Nominal pengeluaran wajib diisi.',
            'note' => 'Catatan pengeluaran harus berupa teks.',
        ]);
    }
}
