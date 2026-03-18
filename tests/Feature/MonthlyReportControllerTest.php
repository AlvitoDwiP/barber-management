<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\Reports\MonthlyReportService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonthlyReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_report_uses_owner_friendly_monthly_formulas_per_month(): void
    {
        $employee = Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);

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

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-01-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$haircut->id],
            'products' => [$pomade->id => 2],
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-02-15',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'services' => [$wash->id],
            'products' => [$gel->id => 1],
        ]);

        Expense::query()->create([
            'expense_date' => '2026-01-12',
            'category' => 'listrik',
            'amount' => '15000.00',
        ]);

        Expense::query()->create([
            'expense_date' => '2026-02-20',
            'category' => 'lainnya',
            'amount' => '10000.00',
        ]);

        Expense::query()->create([
            'expense_date' => '2026-03-05',
            'category' => 'beli alat',
            'amount' => '25000.00',
        ]);

        $rows = app(MonthlyReportService::class)->getMonthlyRevenueReport(2026)->keyBy('month_number');

        $this->assertSame('100000.00', $rows->get(1)['service_revenue']);
        $this->assertSame('40000.00', $rows->get(1)['product_revenue']);
        $this->assertSame('140000.00', $rows->get(1)['total_revenue']);
        $this->assertSame('15000.00', $rows->get(1)['expenses']);
        $this->assertSame('60000.00', $rows->get(1)['employee_fees']);
        $this->assertSame('60000.00', $rows->get(1)['employee_commissions']);
        $this->assertSame('80000.00', $rows->get(1)['barber_income']);
        $this->assertSame('65000.00', $rows->get(1)['profit']);
        $this->assertSame('65000.00', $rows->get(1)['net_profit']);

        $this->assertSame('50000.00', $rows->get(2)['service_revenue']);
        $this->assertSame('30000.00', $rows->get(2)['product_revenue']);
        $this->assertSame('80000.00', $rows->get(2)['total_revenue']);
        $this->assertSame('10000.00', $rows->get(2)['expenses']);
        $this->assertSame('30000.00', $rows->get(2)['employee_fees']);
        $this->assertSame('30000.00', $rows->get(2)['employee_commissions']);
        $this->assertSame('50000.00', $rows->get(2)['barber_income']);
        $this->assertSame('40000.00', $rows->get(2)['profit']);
        $this->assertSame('40000.00', $rows->get(2)['net_profit']);

        $this->assertSame('0.00', $rows->get(3)['service_revenue']);
        $this->assertSame('0.00', $rows->get(3)['product_revenue']);
        $this->assertSame('0.00', $rows->get(3)['total_revenue']);
        $this->assertSame('25000.00', $rows->get(3)['expenses']);
        $this->assertSame('0.00', $rows->get(3)['employee_fees']);
        $this->assertSame('0.00', $rows->get(3)['employee_commissions']);
        $this->assertSame('0.00', $rows->get(3)['barber_income']);
        $this->assertSame('-25000.00', $rows->get(3)['profit']);
        $this->assertSame('-25000.00', $rows->get(3)['net_profit']);

        $februarySummary = app(MonthlyReportService::class)->getCurrentMonthSummary(Carbon::parse('2026-02-01'));

        $this->assertSame([
            'service_revenue' => '50000.00',
            'product_revenue' => '30000.00',
            'total_revenue' => '80000.00',
            'expenses' => '10000.00',
            'employee_fees' => '30000.00',
            'employee_commissions' => '30000.00',
            'barber_income' => '50000.00',
            'profit' => '40000.00',
            'net_profit' => '40000.00',
        ], $februarySummary);
        $this->assertSame(
            array_intersect_key($rows->get(2), array_flip([
                'service_revenue',
                'product_revenue',
                'total_revenue',
                'expenses',
                'employee_fees',
                'employee_commissions',
                'barber_income',
                'profit',
                'net_profit',
            ])),
            $februarySummary
        );

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.monthly', ['year' => 2026]));

        $response->assertOk();
        $response->assertSeeText('Pendapatan layanan');
        $response->assertSeeText('Pendapatan produk');
        $response->assertSeeText('Total pendapatan');
        $response->assertSeeText('Total komisi pegawai');
        $response->assertSeeText('Pengeluaran');
        $response->assertSeeText('Laba bersih');
        $response->assertDontSeeText('Total Pemasukan Barber');
        $response->assertDontSeeText('Keuntungan');
        $response->assertSeeText('Januari 2026');
        $response->assertSeeText('Februari 2026');
        $response->assertSeeText('Maret 2026');
        $response->assertSeeText('Rp 100.000');
        $response->assertSeeText('Rp 40.000');
        $response->assertSeeText('Rp 140.000');
        $response->assertSeeText('Rp 15.000');
        $response->assertSeeText('Rp 60.000');
        $response->assertSeeText('Rp 65.000');
        $response->assertSeeText('Rp 50.000');
        $response->assertSeeText('Rp 30.000');
        $response->assertSeeText('Rp 10.000');
        $response->assertSeeText('Rp -25.000');
    }

    public function test_monthly_report_shows_empty_state_when_year_has_no_data(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.monthly', ['year' => 2026]));

        $response->assertOk();
        $response->assertSeeText('Belum ada data pada tahun ini');
        $response->assertSeeText('Belum ada transaksi atau pengeluaran yang tercatat untuk tahun 2026.');
        $response->assertDontSeeText('Januari 2026');
    }

    public function test_monthly_report_can_export_csv_using_active_year_filter(): void
    {
        $employee = Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);

        $service = Service::query()->create([
            'name' => 'Haircut Premium',
            'price' => '100000.00',
        ]);

        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '20000.00',
            'stock' => 20,
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-01-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [$product->id => 2],
        ]);

        Expense::query()->create([
            'expense_date' => '2026-01-12',
            'category' => 'listrik',
            'amount' => '15000.00',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.monthly.export.csv', ['year' => 2026]));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=laporan-bulanan-2026.csv');

        $csv = $this->parseCsv($response->streamedContent());

        $this->assertSame(
            ['Bulan', 'Pendapatan layanan', 'Pendapatan produk', 'Total pendapatan', 'Total komisi pegawai', 'Pengeluaran', 'Laba bersih'],
            $csv[0]
        );
        $this->assertSame(['Januari 2026', '100000', '40000', '140000', '60000', '15000', '65000'], $csv[1]);
        $this->assertSame(['Total', '100000', '40000', '140000', '60000', '15000', '65000'], end($csv));
    }

    public function test_monthly_report_stays_historical_after_master_commission_changes(): void
    {
        $employee = Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);

        $service = Service::query()->create([
            'name' => 'Haircut Premium',
            'price' => '100000.00',
        ]);

        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '20000.00',
            'stock' => 20,
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-01-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [$product->id => 2],
        ]);

        Expense::query()->create([
            'expense_date' => '2026-01-12',
            'category' => 'listrik',
            'amount' => '15000.00',
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

        $row = app(MonthlyReportService::class)->getMonthlyRevenueReport(2026)->firstWhere('month_number', 1);

        $this->assertSame('100000.00', $row['service_revenue']);
        $this->assertSame('40000.00', $row['product_revenue']);
        $this->assertSame('140000.00', $row['total_revenue']);
        $this->assertSame('60000.00', $row['employee_fees']);
        $this->assertSame('80000.00', $row['barber_income']);
        $this->assertSame('65000.00', $row['profit']);
        $this->assertSame('65000.00', $row['net_profit']);
    }

    public function test_monthly_report_keeps_exact_decimal_owner_metrics_for_sensitive_commission_edges(): void
    {
        $employee = Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
            'employment_type' => 'permanent',
        ]);
        $service = Service::query()->create([
            'name' => 'Color Consultation',
            'price' => '1000.00',
            'commission_type' => 'percent',
            'commission_value' => '66.67',
        ]);
        $product = Product::query()->create([
            'name' => 'Ampoule Sample',
            'price' => '100.00',
            'stock' => 10,
            'commission_type' => 'fixed',
            'commission_value' => '0.01',
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-01-18',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [$product->id => 3],
        ]);

        Expense::query()->create([
            'expense_date' => '2026-01-18',
            'category' => 'lainnya',
            'amount' => '0.01',
        ]);

        $row = app(MonthlyReportService::class)->getMonthlyRevenueReport(2026)->firstWhere('month_number', 1);

        $this->assertSame('1000.00', $row['service_revenue']);
        $this->assertSame('300.00', $row['product_revenue']);
        $this->assertSame('1300.00', $row['total_revenue']);
        $this->assertSame('0.01', $row['expenses']);
        $this->assertSame('666.73', $row['employee_fees']);
        $this->assertSame('666.73', $row['employee_commissions']);
        $this->assertSame('633.27', $row['barber_income']);
        $this->assertSame('633.26', $row['profit']);
        $this->assertSame('633.26', $row['net_profit']);
    }

    private function parseCsv(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = preg_split("/\r\n|\n|\r/", trim($content)) ?: [];

        return array_map(fn (string $line): array => str_getcsv($line), $lines);
    }
}
