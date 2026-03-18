<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\FreelancePayment;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreelancePayrollControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_freelance_page_only_shows_freelance_commissions(): void
    {
        $user = User::factory()->create();
        $freelanceEmployee = Employee::query()->create([
            'name' => 'Budi Freelance',
            'employment_type' => 'freelance',
        ]);
        $permanentEmployee = Employee::query()->create([
            'name' => 'Andi Permanent',
            'employment_type' => 'permanent',
        ]);
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '100000.00',
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-16',
            'employee_id' => $freelanceEmployee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [],
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-16',
            'employee_id' => $permanentEmployee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [],
        ]);

        $response = $this->actingAs($user)->get(route('payroll.freelance.index', [
            'start_date' => '2026-03-16',
            'end_date' => '2026-03-16',
        ]));

        $response->assertOk();
        $response->assertSee('Budi Freelance');
        $response->assertDontSee('Andi Permanent');
        $response->assertSee('Belum Dibayar');
        $response->assertSee('Rp 100.000', false);
        $response->assertSee('Rp 50.000', false);
    }

    public function test_freelance_payment_flow_creates_expense_and_marks_payment_paid(): void
    {
        $user = User::factory()->create();
        $employee = Employee::query()->create([
            'name' => 'Budi Freelance',
            'employment_type' => 'freelance',
        ]);
        $service = Service::query()->create([
            'name' => 'Coloring',
            'price' => '120000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Serum',
            'price' => '40000.00',
            'stock' => 5,
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-16',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'services' => [$service->id],
            'products' => [$product->id => 2],
        ]);

        $prepareResponse = $this->actingAs($user)->post(route('payroll.freelance.prepare-payment'), [
            'employee_id' => $employee->id,
            'work_date' => '2026-03-16',
        ]);

        $payment = FreelancePayment::query()->firstOrFail();

        $prepareResponse->assertRedirect(route('expenses.create', ['freelance_payment' => $payment->id]));
        $this->assertSame('70000.00', $payment->total_commission);
        $this->assertSame(FreelancePayment::STATUS_UNPAID, $payment->payment_status);

        $createResponse = $this->actingAs($user)->get(route('expenses.create', ['freelance_payment' => $payment->id]));

        $createResponse->assertOk();
        $createResponse->assertSee('Pembayaran komisi freelance Budi Freelance', false);
        $createResponse->assertSee('bayar freelance');

        $storeResponse = $this->actingAs($user)->post(route('expenses.store'), [
            'freelance_payment_id' => $payment->id,
            'expense_date' => '2026-03-17',
            'category' => Expense::CATEGORY_PAY_FREELANCE,
            'amount' => '70000.00',
            'note' => 'Pembayaran komisi freelance Budi Freelance untuk transaksi tanggal 16 Maret 2026 sebesar Rp 70.000',
        ]);

        $storeResponse->assertRedirect(route('payroll.freelance.index', [
            'start_date' => '2026-03-16',
            'end_date' => '2026-03-16',
            'employee_id' => $employee->id,
        ]));
        $this->assertDatabaseHas('expenses', [
            'category' => Expense::CATEGORY_PAY_FREELANCE,
            'amount' => '70000.00',
        ]);
        $this->assertDatabaseHas('freelance_payments', [
            'id' => $payment->id,
            'payment_status' => FreelancePayment::STATUS_PAID,
        ]);

        $payment->refresh();

        $this->assertNotNull($payment->expense_id);
        $this->assertNotNull($payment->paid_at);

        $indexResponse = $this->actingAs($user)->get(route('payroll.freelance.index', [
            'start_date' => '2026-03-16',
            'end_date' => '2026-03-16',
            'employee_id' => $employee->id,
        ]));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Sudah Dibayar');
        $indexResponse->assertDontSee('Belum Dibayar');
        $indexResponse->assertDontSee('Bayar Gaji');
    }

    public function test_paid_freelance_settlement_cannot_be_paid_twice(): void
    {
        $user = User::factory()->create();
        $employee = Employee::query()->create([
            'name' => 'Budi Freelance',
            'employment_type' => 'freelance',
        ]);
        $service = Service::query()->create([
            'name' => 'Hair Spa',
            'price' => '100000.00',
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-16',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [],
        ]);

        $this->actingAs($user)->post(route('payroll.freelance.prepare-payment'), [
            'employee_id' => $employee->id,
            'work_date' => '2026-03-16',
        ]);

        $payment = FreelancePayment::query()->firstOrFail();

        $this->actingAs($user)->post(route('expenses.store'), [
            'freelance_payment_id' => $payment->id,
            'expense_date' => '2026-03-16',
            'category' => Expense::CATEGORY_PAY_FREELANCE,
            'amount' => '50000.00',
            'note' => 'Pembayaran pertama',
        ]);

        $response = $this->actingAs($user)->post(route('payroll.freelance.prepare-payment'), [
            'employee_id' => $employee->id,
            'work_date' => '2026-03-16',
        ]);

        $response->assertRedirect(route('payroll.freelance.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_index_and_guard_normalize_paid_status_from_existing_settlement_markers(): void
    {
        $user = User::factory()->create();
        $employee = Employee::query()->create([
            'name' => 'Sari Freelance',
            'employment_type' => 'freelance',
        ]);
        $service = Service::query()->create([
            'name' => 'Hair Spa',
            'price' => '100000.00',
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-16',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [],
        ]);

        $expense = Expense::query()->create([
            'expense_date' => '2026-03-17',
            'category' => Expense::CATEGORY_PAY_FREELANCE,
            'amount' => '50000.00',
            'note' => 'Pembayaran komisi freelance Sari Freelance',
        ]);

        $payment = FreelancePayment::query()->create([
            'employee_id' => $employee->id,
            'work_date' => '2026-03-16',
            'total_service_amount' => '100000.00',
            'service_commission' => '50000.00',
            'total_product_qty' => 0,
            'product_commission' => '0.00',
            'total_commission' => '50000.00',
            'expense_id' => $expense->id,
            'paid_at' => null,
            'payment_status' => FreelancePayment::STATUS_UNPAID,
        ]);

        $indexResponse = $this->actingAs($user)->get(route('payroll.freelance.index', [
            'start_date' => '2026-03-16',
            'end_date' => '2026-03-16',
            'employee_id' => $employee->id,
        ]));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Sudah Dibayar');
        $indexResponse->assertDontSee('Belum Dibayar');
        $indexResponse->assertDontSee('Bayar Gaji');

        $response = $this->actingAs($user)->post(route('payroll.freelance.prepare-payment'), [
            'employee_id' => $employee->id,
            'work_date' => '2026-03-16',
        ]);

        $response->assertRedirect(route('payroll.freelance.index'));
        $response->assertSessionHas('error', 'Komisi freelance untuk pegawai dan tanggal ini sudah dibayar.');

        $this->assertDatabaseHas('freelance_payments', [
            'id' => $payment->id,
            'expense_id' => $expense->id,
            'payment_status' => FreelancePayment::STATUS_PAID,
        ]);

        $payment->refresh();

        $this->assertNotNull($payment->paid_at);
    }

    public function test_freelance_summary_stays_based_on_transaction_item_snapshots_after_master_commission_changes(): void
    {
        $user = User::factory()->create();
        $employee = Employee::query()->create([
            'name' => 'Budi Freelance',
            'employment_type' => 'freelance',
        ]);
        $service = Service::query()->create([
            'name' => 'Coloring',
            'price' => '120000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Serum',
            'price' => '40000.00',
            'stock' => 5,
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-16',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'services' => [$service->id],
            'products' => [$product->id => 2],
        ]);

        $service->update([
            'commission_type' => 'percent',
            'commission_value' => '10.00',
        ]);
        $product->update([
            'commission_type' => 'percent',
            'commission_value' => '1.00',
        ]);
        CommissionSetting::query()->update([
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '5.00',
            'default_product_commission_type' => 'percent',
            'default_product_commission_value' => '2.00',
        ]);

        $response = $this->actingAs($user)->post(route('payroll.freelance.prepare-payment'), [
            'employee_id' => $employee->id,
            'work_date' => '2026-03-16',
        ]);

        $payment = FreelancePayment::query()->firstOrFail();

        $response->assertRedirect(route('expenses.create', ['freelance_payment' => $payment->id]));
        $this->assertSame('60000.00', $payment->service_commission);
        $this->assertSame('10000.00', $payment->product_commission);
        $this->assertSame('70000.00', $payment->total_commission);
    }
}
