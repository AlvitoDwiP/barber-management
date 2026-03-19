<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Service;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

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
