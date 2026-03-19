<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Reports\MonthlyReportService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_today_summary_uses_transaction_date_instead_of_created_at(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);

        $this->travelTo(Carbon::parse('2026-03-15 09:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $employee = $this->createEmployee();

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260315-001',
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '100000.00',
            'discount_amount' => '0.00',
            'total_amount' => '100000.00',
            'notes' => 'Masuk ringkasan hari ini',
            'created_at' => Carbon::parse('2026-03-14 23:45:00', config('app.timezone')),
            'updated_at' => Carbon::parse('2026-03-14 23:45:00', config('app.timezone')),
        ]);

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260315-002',
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'subtotal_amount' => '75000.00',
            'discount_amount' => '0.00',
            'total_amount' => '75000.00',
            'notes' => 'Tetap masuk meski dibuat kemarin',
            'created_at' => Carbon::parse('2026-03-14 22:30:00', config('app.timezone')),
            'updated_at' => Carbon::parse('2026-03-14 22:30:00', config('app.timezone')),
        ]);

        Transaction::query()->create([
            'transaction_code' => 'TRX-20260314-001',
            'transaction_date' => '2026-03-14',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'subtotal_amount' => '50000.00',
            'discount_amount' => '0.00',
            'total_amount' => '50000.00',
            'notes' => 'Tidak boleh masuk ringkasan hari ini',
            'created_at' => Carbon::parse('2026-03-15 08:15:00', config('app.timezone')),
            'updated_at' => Carbon::parse('2026-03-15 08:15:00', config('app.timezone')),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('todaySummary', fn (array $summary): bool => $summary === [
            'today_revenue' => '175000.00',
            'today_transactions' => 2,
            'today_cash' => '100000.00',
            'today_qr' => '75000.00',
        ]);
    }

    public function test_dashboard_monthly_summary_still_uses_transaction_date_for_current_month(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);

        $this->travelTo(Carbon::parse('2026-03-15 09:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $employee = $this->createEmployee();
        $haircut = Service::query()->create([
            'name' => 'Haircut Premium',
            'price' => '100000.00',
        ]);
        $wash = Service::query()->create([
            'name' => 'Hair Wash',
            'price' => '50000.00',
        ]);
        $pomade = Product::query()->create([
            'name' => 'Pomade',
            'price' => '20000.00',
            'stock' => 20,
        ]);
        $gel = Product::query()->create([
            'name' => 'Gel',
            'price' => '30000.00',
            'stock' => 20,
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$haircut->id], [$pomade->id => 2]),
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-01',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'items' => $this->transactionItems($employee->id, [$wash->id], [$gel->id => 1]),
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-02-28',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$haircut->id]),
        ]);

        Expense::query()->create([
            'expense_date' => '2026-03-10',
            'category' => 'listrik',
            'amount' => '30000.00',
            'note' => 'Biaya bulan berjalan',
        ]);

        Expense::query()->create([
            'expense_date' => '2026-02-20',
            'category' => 'lainnya',
            'amount' => '45000.00',
            'note' => 'Biaya bulan lalu',
        ]);

        $marchRow = app(MonthlyReportService::class)
            ->getMonthlyRevenueReport(2026)
            ->firstWhere('month_number', 3);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('monthlySummary', fn (array $summary): bool => $summary === [
            'service_revenue' => '150000.00',
            'product_revenue' => '70000.00',
            'total_revenue' => '220000.00',
            'expenses' => '30000.00',
            'employee_fees' => '90000.00',
            'employee_commissions' => '90000.00',
            'barber_income' => '130000.00',
            'profit' => '100000.00',
            'net_profit' => '100000.00',
        ]);
        $response->assertViewHas('monthlySummary', fn (array $summary): bool => $summary === array_intersect_key(
            $marchRow,
            array_flip([
                'service_revenue',
                'product_revenue',
                'total_revenue',
                'expenses',
                'employee_fees',
                'employee_commissions',
                'barber_income',
                'profit',
                'net_profit',
            ])
        ));
        $response->assertSeeText('Pendapatan layanan');
        $response->assertSeeText('Pendapatan produk');
        $response->assertSeeText('Pengeluaran');
        $response->assertSeeText('Total pemasukan barber');
        $response->assertSeeText('Keuntungan');
        $response->assertDontSeeText('Pendapatan bulan ini');
        $response->assertDontSeeText('Estimasi laba bulan ini');
    }

    public function test_dashboard_monthly_summary_stays_historical_after_master_commission_changes(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);

        $this->travelTo(Carbon::parse('2026-03-15 09:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut Premium',
            'price' => '100000.00',
        ]);
        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '20000.00',
            'stock' => 20,
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-15',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 2]),
        ]);

        Expense::query()->create([
            'expense_date' => '2026-03-10',
            'category' => 'listrik',
            'amount' => '15000.00',
            'note' => 'Biaya bulan berjalan',
        ]);

        $service->update([
            'price' => '250000.00',
            'commission_type' => 'percent',
            'commission_value' => '10.00',
        ]);
        $product->update([
            'price' => '50000.00',
            'commission_type' => 'percent',
            'commission_value' => '1.00',
        ]);
        CommissionSetting::query()->update([
            'default_service_commission_type' => 'percent',
            'default_service_commission_value' => '5.00',
            'default_product_commission_type' => 'percent',
            'default_product_commission_value' => '2.00',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('monthlySummary', fn (array $summary): bool => $summary === [
            'service_revenue' => '100000.00',
            'product_revenue' => '40000.00',
            'total_revenue' => '140000.00',
            'expenses' => '15000.00',
            'employee_fees' => '60000.00',
            'employee_commissions' => '60000.00',
            'barber_income' => '80000.00',
            'profit' => '65000.00',
            'net_profit' => '65000.00',
        ]);
    }

    public function test_dashboard_keeps_exact_decimal_strings_for_today_and_monthly_sensitive_nominals(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);

        $this->travelTo(Carbon::parse('2026-03-18 09:00:00', config('app.timezone')));

        $user = User::factory()->create();
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Color Consultation',
            'price' => '1000.00',
            'commission_type' => 'percent',
            'commission_value' => '66.67',
        ]);
        $product = Product::query()->create([
            'name' => 'Ampoule Sample',
            'price' => '0.01',
            'stock' => 10,
            'commission_type' => 'fixed',
            'commission_value' => '0.01',
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-18',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$service->id], [$product->id => 1]),
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-18',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'items' => $this->transactionItems($employee->id, [], [$product->id => 2]),
        ]);

        Expense::query()->create([
            'expense_date' => '2026-03-18',
            'category' => 'lainnya',
            'amount' => '0.01',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('todaySummary', fn (array $summary): bool => $summary === [
            'today_revenue' => '1000.03',
            'today_transactions' => 2,
            'today_cash' => '1000.01',
            'today_qr' => '0.02',
        ]);
        $response->assertViewHas('monthlySummary', fn (array $summary): bool => $summary === [
            'service_revenue' => '1000.00',
            'product_revenue' => '0.03',
            'total_revenue' => '1000.03',
            'expenses' => '0.01',
            'employee_fees' => '666.73',
            'employee_commissions' => '666.73',
            'barber_income' => '333.30',
            'profit' => '333.29',
            'net_profit' => '333.29',
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
