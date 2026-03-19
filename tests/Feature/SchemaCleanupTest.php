<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\PayrollService;
use App\Services\TransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchemaCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_item_is_the_only_transaction_items_model_entry_point(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '50000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $this->assertFalse(class_exists(\App\Models\TransactionDetail::class));
        $this->assertInstanceOf(TransactionItem::class, $transaction->transactionItems()->first());
        $this->assertFalse(method_exists($transaction, 'transactionDetails'));
    }

    public function test_transaction_payroll_relation_uses_payroll_id_as_primary_link(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Keratin',
            'price' => '450000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $payrollPeriod = PayrollPeriod::query()->create([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => PayrollPeriod::STATUS_OPEN,
            'closed_at' => null,
        ]);

        DB::table('transactions')
            ->where('id', $transaction->id)
            ->update([
                'payroll_id' => $payrollPeriod->id,
                'payroll_period_id' => $payrollPeriod->id,
            ]);

        $transaction->refresh();

        $this->assertTrue($transaction->payrollPeriod->is($payrollPeriod));
        $this->assertFalse(method_exists($transaction, 'assignedPayrollPeriod'));
        $this->assertTrue($payrollPeriod->transactions()->whereKey($transaction->id)->exists());
    }

    public function test_payroll_period_delete_is_restricted_when_historical_rows_exist(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Highlight',
            'price' => '250000.00',
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id]),
        ]);

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        $payrollPeriod = app(PayrollService::class)->closePayroll($payrollPeriod);

        $this->expectException(QueryException::class);

        $payrollPeriod->delete();
    }

    public function test_transaction_date_is_not_nullable_at_database_level(): void
    {
        $employee = $this->createEmployee();

        $this->expectException(QueryException::class);

        DB::table('transactions')->insert([
            'transaction_code' => 'TRX-NULL-DATE',
            'transaction_date' => null,
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '0.00',
            'discount_amount' => '0.00',
            'total_amount' => '0.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);
    }
}
