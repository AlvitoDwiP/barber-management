<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\Reports\MonthlyReportService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_report_uses_barber_income_and_profit_formulas_per_month(): void
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

        $this->assertSame(100000.0, $rows->get(1)['service_revenue']);
        $this->assertSame(40000.0, $rows->get(1)['product_revenue']);
        $this->assertSame(15000.0, $rows->get(1)['expenses']);
        $this->assertSame(60000.0, $rows->get(1)['employee_fees']);
        $this->assertSame(80000.0, $rows->get(1)['barber_income']);
        $this->assertSame(65000.0, $rows->get(1)['profit']);

        $this->assertSame(50000.0, $rows->get(2)['service_revenue']);
        $this->assertSame(30000.0, $rows->get(2)['product_revenue']);
        $this->assertSame(10000.0, $rows->get(2)['expenses']);
        $this->assertSame(30000.0, $rows->get(2)['employee_fees']);
        $this->assertSame(50000.0, $rows->get(2)['barber_income']);
        $this->assertSame(40000.0, $rows->get(2)['profit']);

        $this->assertSame(0.0, $rows->get(3)['service_revenue']);
        $this->assertSame(0.0, $rows->get(3)['product_revenue']);
        $this->assertSame(25000.0, $rows->get(3)['expenses']);
        $this->assertSame(0.0, $rows->get(3)['employee_fees']);
        $this->assertSame(0.0, $rows->get(3)['barber_income']);
        $this->assertSame(-25000.0, $rows->get(3)['profit']);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.monthly', ['year' => 2026]));

        $response->assertOk();
        $response->assertSeeText('Pendapatan Layanan');
        $response->assertSeeText('Pendapatan Produk');
        $response->assertSeeText('Pengeluaran');
        $response->assertSeeText('Total Pemasukan Barber');
        $response->assertSeeText('Keuntungan');
        $response->assertSeeText('Januari 2026');
        $response->assertSeeText('Februari 2026');
        $response->assertSeeText('Maret 2026');
        $response->assertSeeText('Rp 100.000');
        $response->assertSeeText('Rp 40.000');
        $response->assertSeeText('Rp 15.000');
        $response->assertSeeText('Rp 80.000');
        $response->assertSeeText('Rp 65.000');
        $response->assertSeeText('Rp 50.000');
        $response->assertSeeText('Rp 30.000');
        $response->assertSeeText('Rp 10.000');
        $response->assertSeeText('Rp -25.000');
    }
}
