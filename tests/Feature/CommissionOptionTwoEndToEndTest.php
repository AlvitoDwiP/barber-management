<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
use App\Models\Employee;
use App\Models\Expense;
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

        $firstTransaction = app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [$product->id => 2],
        ]);

        $service->update([
            'commission_type' => 'percent',
            'commission_value' => '50.00',
        ]);
        $product->update([
            'commission_type' => 'percent',
            'commission_value' => '10.00',
        ]);

        $secondTransaction = app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-11',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'services' => [$service->id],
            'products' => [$product->id => 1],
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

        $this->assertSame([
            'service_revenue' => 200000.0,
            'product_revenue' => 150000.0,
            'total_revenue' => 350000.0,
            'expenses' => 15000.0,
            'employee_fees' => 106000.0,
            'employee_commissions' => 106000.0,
            'barber_income' => 244000.0,
            'profit' => 229000.0,
            'net_profit' => 229000.0,
        ], $monthlySummary);

        $employeePerformance = app(EmployeePerformanceReportService::class)
            ->getEmployeePerformanceReport('2026-03-01', '2026-03-31', $employee->id)
            ->first();

        $this->assertSame('Budi', $employeePerformance['employee_name']);
        $this->assertSame(2, $employeePerformance['total_transactions']);
        $this->assertSame(2, $employeePerformance['total_services']);
        $this->assertSame(200000.0, $employeePerformance['service_revenue']);
        $this->assertSame(3, $employeePerformance['total_products']);
        $this->assertSame(150000.0, $employeePerformance['product_revenue']);
        $this->assertSame(106000.0, $employeePerformance['total_commission']);

        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertViewHas('monthlySummary', fn (array $summary): bool => $summary === $monthlySummary);

        $this->assertSame(2, Transaction::query()->count());
        $this->assertSame(17, $product->fresh()->stock);
    }
}
