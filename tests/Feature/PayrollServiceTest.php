<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollResult;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\TransactionService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_close_payroll_creates_final_snapshot_from_transaction_item_snapshots(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);

        $this->createServiceTransaction($employee, $service, '2026-03-10');

        $service->update(['price' => '90000.00']);

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $payrollResult = PayrollResult::query()->where('payroll_period_id', $payrollPeriod->id)->firstOrFail();
        $transaction = Transaction::query()->firstOrFail();

        $this->assertSame(PayrollPeriod::STATUS_CLOSED, $payrollPeriod->fresh()->status);
        $this->assertSame($payrollPeriod->id, $transaction->fresh()->payroll_id);
        $this->assertSame($payrollPeriod->id, $transaction->fresh()->payroll_period_id);
        $this->assertSame($employee->id, $payrollResult->employee_id);
        $this->assertSame('Budi', $payrollResult->employee_name);
        $this->assertSame(1, $payrollResult->total_transaction_count);
        $this->assertSame(1, $payrollResult->total_transactions);
        $this->assertSame(1, $payrollResult->total_services);
        $this->assertSame(0, $payrollResult->total_products);
        $this->assertSame('50000.00', $payrollResult->total_service_amount);
        $this->assertSame('25000.00', $payrollResult->total_service_commission);
        $this->assertSame('0.00', $payrollResult->total_product_commission);
        $this->assertSame('25000.00', $payrollResult->total_commission);
    }

    public function test_closed_payroll_snapshot_does_not_change_after_master_data_changes(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Keratin',
            'price' => '450000.00',
        ]);

        $this->createServiceTransaction($employee, $service, '2026-03-10');

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $service->update([
            'price' => '600000.00',
            'commission_type' => 'percent',
            'commission_value' => '10.00',
        ]);
        CommissionSetting::query()->update([
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '10.00',
            'default_product_commission_type' => 'percent',
            'default_product_commission_value' => '1.00',
        ]);
        $employee->update(['name' => 'Budi Updated']);

        $payrollResult = PayrollResult::query()->where('payroll_period_id', $payrollPeriod->id)->firstOrFail();

        $this->assertSame('Budi', $payrollResult->employee_name);
        $this->assertSame('450000.00', $payrollResult->total_service_amount);
        $this->assertSame('225000.00', $payrollResult->total_service_commission);
        $this->assertSame('225000.00', $payrollResult->total_commission);
    }

    public function test_close_payroll_uses_transaction_item_commission_snapshots_even_if_master_rules_change_before_close(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '100000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '50000.00',
            'stock' => 10,
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
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
        CommissionSetting::query()->update([
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '5.00',
            'default_product_commission_type' => 'percent',
            'default_product_commission_value' => '2.00',
        ]);

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $payrollResult = PayrollResult::query()->where('payroll_period_id', $payrollPeriod->id)->firstOrFail();

        $this->assertSame('100000.00', $payrollResult->total_service_amount);
        $this->assertSame('50000.00', $payrollResult->total_service_commission);
        $this->assertSame('10000.00', $payrollResult->total_product_commission);
        $this->assertSame('60000.00', $payrollResult->total_commission);
    }

    public function test_close_payroll_preserves_exact_sensitive_commission_totals_without_float_drift(): void
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

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-18',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$trim->id, $color->id], [$sample->id => 3]),
        ]);

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $payrollResult = PayrollResult::query()->where('payroll_period_id', $payrollPeriod->id)->firstOrFail();

        $this->assertSame('2000.00', $payrollResult->total_service_amount);
        $this->assertSame('1000.00', $payrollResult->total_service_commission);
        $this->assertSame('0.03', $payrollResult->total_product_commission);
        $this->assertSame('1000.03', $payrollResult->total_commission);
    }

    public function test_transactions_in_closed_payroll_cannot_be_updated_or_deleted(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Perm',
            'price' => '300000.00',
        ]);

        $transaction = $this->createServiceTransaction($employee, $service, '2026-03-10');
        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $transactionService = app(TransactionService::class);

        try {
            $transactionService->replaceTransaction($transaction, [
                'transaction_date' => '2026-03-11',
                'employee_id' => $employee->id,
                'payment_method' => 'qr',
                'items' => $this->transactionItems($employee->id, [$service->id]),
            ]);

            $this->fail('Expected update to be blocked for closed payroll transaction.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('tidak dapat diubah', $exception->getMessage());
        }

        try {
            $transactionService->deleteTransaction($transaction);

            $this->fail('Expected delete to be blocked for closed payroll transaction.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('tidak dapat diubah atau dihapus', $exception->getMessage());
        }

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_closed_payroll_detail_page_reads_from_payroll_results_snapshot(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Highlight',
            'price' => '250000.00',
        ]);

        $this->createServiceTransaction($employee, $service, '2026-03-10');

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $employee->update(['name' => 'Nama Baru']);
        $service->update(['price' => '350000.00']);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('payroll.show', $payrollPeriod));

        $response->assertOk();
        $response->assertSee('Budi');
        $response->assertDontSee('Nama Baru');
        $response->assertSee('250.000', false);
        $response->assertSee('125.000', false);
    }

    public function test_duplicate_payroll_result_for_same_employee_and_period_is_rejected(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);

        $this->createServiceTransaction($employee, $service, '2026-03-10');

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $existing = PayrollResult::query()->where('payroll_period_id', $payrollPeriod->id)->firstOrFail();

        $this->expectException(QueryException::class);

        DB::table('payroll_results')->insert([
            'payroll_period_id' => $payrollPeriod->id,
            'employee_id' => $existing->employee_id,
            'employee_name' => $existing->employee_name,
            'total_transactions' => 1,
            'total_transaction_count' => 1,
            'total_services' => 1,
            'total_products' => 0,
            'total_service_amount' => '50000.00',
            'total_service_commission' => '25000.00',
            'total_product_commission' => '0.00',
            'total_commission' => '25000.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_only_one_open_payroll_is_allowed_by_service_guard(): void
    {
        app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Masih ada payroll open yang belum ditutup.');

        app(PayrollService::class)->openPayroll('2026-04-01', '2026-04-30');
    }

    public function test_close_payroll_only_assigns_permanent_employee_transactions(): void
    {
        $permanentEmployee = Employee::query()->create([
            'name' => 'Andi',
            'employment_type' => 'permanent',
        ]);
        $freelanceEmployee = Employee::query()->create([
            'name' => 'Budi Freelance',
            'employment_type' => 'freelance',
        ]);
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);

        $permanentTransaction = $this->createServiceTransaction($permanentEmployee, $service, '2026-03-10');
        $freelanceTransaction = $this->createServiceTransaction($freelanceEmployee, $service, '2026-03-10');

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $this->assertDatabaseHas('payroll_results', [
            'payroll_period_id' => $payrollPeriod->id,
            'employee_id' => $permanentEmployee->id,
        ]);
        $this->assertDatabaseMissing('payroll_results', [
            'payroll_period_id' => $payrollPeriod->id,
            'employee_id' => $freelanceEmployee->id,
        ]);
        $this->assertSame($payrollPeriod->id, $permanentTransaction->fresh()->payroll_id);
        $this->assertNull($freelanceTransaction->fresh()->payroll_id);
    }

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name' => 'Budi',
            'employment_type' => 'permanent',
        ]);
    }

    private function createServiceTransaction(Employee $employee, Service $service, string $transactionDate): Transaction
    {
        return app(TransactionService::class)->recordTransaction([
            'transaction_date' => $transactionDate,
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);
    }
}
