<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\FreelancePayment;
use App\Models\PayrollResult;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\Reports\EmployeePerformanceReportService;
use App\Services\Reports\MonthlyReportService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CommissionOptionTwoEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_option_two_stays_consistent_across_settings_transactions_payroll_and_reports(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        $this->travelTo(Carbon::parse('2026-03-20 09:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $employee = Employee::query()->create([
            'name' => 'Budi',
            'employment_type' => 'permanent',
            'status' => 'tetap',
        ]);
        $service = Service::query()->create([
            'name' => 'Haircut',
            'price' => '100000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '50000.00',
            'stock' => 20,
        ]);

        $this->actingAs($user)->put(route('settings.commission.update'), [
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '35.00',
            'default_product_commission_type' => 'fixed',
            'default_product_commission_value' => '8000.00',
        ])->assertRedirect(route('settings.commission.edit'));

        $firstTransaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 2]),
        ]);

        $service->update([
            'commission_type' => 'percent',
            'commission_value' => '50.00',
        ]);
        $product->update([
            'commission_type' => 'percent',
            'commission_value' => '10.00',
        ]);

        $secondTransaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-11',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 1]),
        ]);

        $this->actingAs($user)->put(route('settings.commission.update'), [
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '5.00',
            'default_product_commission_type' => 'percent',
            'default_product_commission_value' => '2.00',
        ])->assertRedirect(route('settings.commission.edit'));

        $service->update([
            'commission_type' => 'percent',
            'commission_value' => '10.00',
        ]);
        $product->update([
            'commission_type' => 'fixed',
            'commission_value' => '1000.00',
        ]);

        Expense::query()->create([
            'expense_date' => '2026-03-12',
            'category' => 'listrik',
            'amount' => '15000.00',
        ]);

        $firstDetails = $firstTransaction->fresh()->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $firstProduct = $firstDetails->firstWhere('item_type', 'product');
        $firstService = $firstDetails->firstWhere('item_type', 'service');

        $this->assertSame('default', $firstService?->commission_source);
        $this->assertSame('percent', $firstService?->commission_type);
        $this->assertSame('35.00', $firstService?->commission_value);
        $this->assertSame('35000.00', $firstService?->commission_amount);
        $this->assertSame('default', $firstProduct?->commission_source);
        $this->assertSame('fixed', $firstProduct?->commission_type);
        $this->assertSame('8000.00', $firstProduct?->commission_value);
        $this->assertSame('16000.00', $firstProduct?->commission_amount);

        $secondDetails = $secondTransaction->fresh()->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $secondProduct = $secondDetails->firstWhere('item_type', 'product');
        $secondService = $secondDetails->firstWhere('item_type', 'service');

        $this->assertSame('override', $secondService?->commission_source);
        $this->assertSame('percent', $secondService?->commission_type);
        $this->assertSame('50.00', $secondService?->commission_value);
        $this->assertSame('50000.00', $secondService?->commission_amount);
        $this->assertSame('override', $secondProduct?->commission_source);
        $this->assertSame('percent', $secondProduct?->commission_type);
        $this->assertSame('10.00', $secondProduct?->commission_value);
        $this->assertSame('5000.00', $secondProduct?->commission_amount);

        $settings = CommissionSetting::query()->findOrFail(1);
        $this->assertSame('percent', $settings->default_service_commission_type);
        $this->assertSame('5.00', $settings->default_service_commission_value);
        $this->assertSame('percent', $settings->default_product_commission_type);
        $this->assertSame('2.00', $settings->default_product_commission_value);

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $payrollResult = PayrollResult::query()->where('payroll_period_id', $payrollPeriod->id)->firstOrFail();

        $this->assertSame('200000.00', $payrollResult->total_service_amount);
        $this->assertSame('85000.00', $payrollResult->total_service_commission);
        $this->assertSame('21000.00', $payrollResult->total_product_commission);
        $this->assertSame('106000.00', $payrollResult->total_commission);

        $monthlySummary = app(MonthlyReportService::class)->getCurrentMonthSummary(Carbon::parse('2026-03-01'));

        $this->assertSame('200000.00', $monthlySummary['service_revenue']);
        $this->assertSame('150000.00', $monthlySummary['product_revenue']);
        $this->assertSame('350000.00', $monthlySummary['total_revenue']);
        $this->assertSame('106000.00', $monthlySummary['barber_commissions']);
        $this->assertSame('15000.00', $monthlySummary['operational_expenses']);
        $this->assertSame('121000.00', $monthlySummary['total_operating_expenses']);
        $this->assertSame('229000.00', $monthlySummary['operating_profit']);

        $employeePerformance = app(EmployeePerformanceReportService::class)
            ->getEmployeePerformanceReport('2026-03-01', '2026-03-31', $employee->id)
            ->first();

        $this->assertSame('Budi', $employeePerformance['employee_name']);
        $this->assertSame(2, $employeePerformance['total_transactions']);
        $this->assertSame(2, $employeePerformance['total_services']);
        $this->assertSame('200000.00', $employeePerformance['service_revenue']);
        $this->assertSame(3, $employeePerformance['total_products']);
        $this->assertSame('150000.00', $employeePerformance['product_revenue']);
        $this->assertSame('106000.00', $employeePerformance['total_commission']);

        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertViewHas('monthlySummary', fn (array $summary): bool => $summary['operating_profit'] === $monthlySummary['operating_profit']
            && $summary['barber_commissions'] === $monthlySummary['barber_commissions']);

        $this->assertSame(2, Transaction::query()->count());
        $this->assertSame(17, $product->fresh()->stock);
    }

    public function test_exact_commission_edges_stay_consistent_across_transaction_payroll_report_and_freelance_settlement(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        $this->travelTo(Carbon::parse('2026-03-20 09:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $permanentEmployee = Employee::query()->create([
            'name' => 'Budi Permanent',
            'employment_type' => 'permanent',
            'status' => 'tetap',
        ]);
        $freelanceEmployee = Employee::query()->create([
            'name' => 'Sari Freelance',
            'employment_type' => 'freelance',
            'status' => 'freelance',
        ]);
        $permanentService = Service::query()->create([
            'name' => 'Color Consultation',
            'price' => '1000.00',
            'commission_type' => 'percent',
            'commission_value' => '66.67',
        ]);
        $freelanceService = Service::query()->create([
            'name' => 'Trim Sensitive',
            'price' => '1000.00',
            'commission_type' => 'percent',
            'commission_value' => '33.33',
        ]);
        $sample = Product::query()->create([
            'name' => 'Ampoule Sample',
            'price' => '100.00',
            'stock' => 20,
            'commission_type' => 'fixed',
            'commission_value' => '0.01',
        ]);

        $permanentTransaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-18',
            'employee_id' => $permanentEmployee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($permanentEmployee->id, [$permanentService->id], [$sample->id => 3]),
        ]);

        $freelanceTransaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-19',
            'employee_id' => $freelanceEmployee->id,
            'payment_method' => 'qr',
            'items' => $this->transactionItems($freelanceEmployee->id, [$freelanceService->id]),
        ]);

        $permanentDetails = $permanentTransaction->fresh()->transactionItems()->orderBy('item_type')->orderBy('id')->get();
        $permanentProduct = $permanentDetails->firstWhere('item_type', 'product');
        $permanentServiceDetail = $permanentDetails->firstWhere('item_type', 'service');
        $freelanceServiceDetail = $freelanceTransaction->fresh()->transactionItems()->firstWhere('item_type', 'service');

        $this->assertSame('66.67', $permanentServiceDetail?->commission_value);
        $this->assertSame('666.70', $permanentServiceDetail?->commission_amount);
        $this->assertSame('0.01', $permanentProduct?->commission_value);
        $this->assertSame('0.03', $permanentProduct?->commission_amount);
        $this->assertSame('33.33', $freelanceServiceDetail?->commission_value);
        $this->assertSame('333.30', $freelanceServiceDetail?->commission_amount);

        $prepareResponse = $this->actingAs($user)->post(route('payroll.freelance.prepare-payment'), [
            'employee_id' => $freelanceEmployee->id,
            'work_date' => '2026-03-19',
        ]);

        $freelancePayment = FreelancePayment::query()->firstOrFail();

        $prepareResponse->assertRedirect(route('expenses.create', ['freelance_payment' => $freelancePayment->id]));
        $this->assertSame('333.30', $freelancePayment->total_commission);

        $this->actingAs($user)->post(route('expenses.store'), [
            'freelance_payment_id' => $freelancePayment->id,
            'expense_date' => '2026-03-20',
            'category' => Expense::CATEGORY_PAY_FREELANCE,
            'amount' => '333.30',
            'note' => 'Pembayaran komisi freelance presisi',
        ])->assertRedirect(route('payroll.freelance.index', [
            'start_date' => '2026-03-19',
            'end_date' => '2026-03-19',
            'employee_id' => $freelanceEmployee->id,
        ]));

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $payrollResult = PayrollResult::query()
            ->where('payroll_period_id', $payrollPeriod->id)
            ->where('employee_id', $permanentEmployee->id)
            ->firstOrFail();

        $this->assertSame('1000.00', $payrollResult->total_service_amount);
        $this->assertSame('666.70', $payrollResult->total_service_commission);
        $this->assertSame('0.03', $payrollResult->total_product_commission);
        $this->assertSame('666.73', $payrollResult->total_commission);

        $monthlySummary = app(MonthlyReportService::class)->getCurrentMonthSummary(Carbon::parse('2026-03-01'));

        $this->assertSame('2000.00', $monthlySummary['service_revenue']);
        $this->assertSame('300.00', $monthlySummary['product_revenue']);
        $this->assertSame('2300.00', $monthlySummary['total_revenue']);
        $this->assertSame('1000.03', $monthlySummary['barber_commissions']);
        $this->assertSame('333.30', $monthlySummary['operational_expenses']);
        $this->assertSame('1333.33', $monthlySummary['total_operating_expenses']);
        $this->assertSame('966.67', $monthlySummary['operating_profit']);

        $employeePerformance = app(EmployeePerformanceReportService::class)
            ->getEmployeePerformanceReport('2026-03-01', '2026-03-31', $permanentEmployee->id)
            ->first();

        $this->assertSame('Budi Permanent', $employeePerformance['employee_name']);
        $this->assertSame(1, $employeePerformance['total_transactions']);
        $this->assertSame(1, $employeePerformance['total_services']);
        $this->assertSame('1000.00', $employeePerformance['service_revenue']);
        $this->assertSame(3, $employeePerformance['total_products']);
        $this->assertSame('300.00', $employeePerformance['product_revenue']);
        $this->assertSame('666.73', $employeePerformance['total_commission']);

        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertViewHas('monthlySummary', fn (array $summary): bool => $summary['operating_profit'] === $monthlySummary['operating_profit']
            && $summary['barber_commissions'] === $monthlySummary['barber_commissions']);

        $this->assertDatabaseHas('expenses', [
            'category' => Expense::CATEGORY_PAY_FREELANCE,
            'amount' => '333.30',
        ]);
        $this->assertDatabaseHas('freelance_payments', [
            'id' => $freelancePayment->id,
            'payment_status' => FreelancePayment::STATUS_PAID,
        ]);
    }

    public function test_mixed_employee_transaction_keeps_payroll_freelance_and_reports_consistent_per_item_snapshot(): void
    {
        $user = User::factory()->create();
        $permanentEmployee = Employee::query()->create([
            'name' => 'Raka Permanent',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PERMANENT,
            'status' => 'tetap',
            'is_active' => true,
        ]);
        $freelanceEmployee = Employee::query()->create([
            'name' => 'Maya Freelance',
            'employment_type' => Employee::EMPLOYMENT_TYPE_FREELANCE,
            'status' => 'freelance',
            'is_active' => true,
        ]);
        $cut = Service::query()->create([
            'name' => 'Cut Pro',
            'price' => '100000.00',
        ]);
        $wash = Service::query()->create([
            'name' => 'Wash Relax',
            'price' => '80000.00',
        ]);

        $transaction = app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-21',
            'employee_id' => $permanentEmployee->id,
            'payment_method' => 'cash',
            'items' => [
                [
                    'item_type' => 'service',
                    'service_id' => $cut->id,
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

        $payrollPeriod = app(PayrollService::class)->openPayroll('2026-03-01', '2026-03-31');
        app(PayrollService::class)->closePayroll($payrollPeriod);

        $payrollResult = PayrollResult::query()->where('payroll_period_id', $payrollPeriod->id)->firstOrFail();

        $this->assertSame($payrollPeriod->id, $transaction->fresh()->payroll_id);
        $this->assertSame($permanentEmployee->id, $payrollResult->employee_id);
        $this->assertSame('Raka Permanent', $payrollResult->employee_name);
        $this->assertSame('100000.00', $payrollResult->total_service_amount);
        $this->assertSame('50000.00', $payrollResult->total_service_commission);
        $this->assertSame('50000.00', $payrollResult->total_commission);

        $performanceRows = app(EmployeePerformanceReportService::class)
            ->getEmployeePerformanceReport('2026-03-01', '2026-03-31')
            ->keyBy('employee_name');

        $this->assertSame('50000.00', $performanceRows['Raka Permanent']['total_commission']);
        $this->assertSame('40000.00', $performanceRows['Maya Freelance']['total_commission']);

        $prepareResponse = $this->actingAs($user)->post(route('payroll.freelance.prepare-payment'), [
            'employee_id' => $freelanceEmployee->id,
            'work_date' => '2026-03-21',
        ]);

        $freelancePayment = FreelancePayment::query()->firstOrFail();

        $prepareResponse->assertRedirect(route('expenses.create', ['freelance_payment' => $freelancePayment->id]));
        $this->assertSame($freelanceEmployee->id, $freelancePayment->employee_id);
        $this->assertSame('40000.00', $freelancePayment->total_commission);
    }
}
