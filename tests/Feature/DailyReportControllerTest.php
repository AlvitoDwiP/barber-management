<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\Reports\DailyReportService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_report_builds_daily_recaps_and_period_summary(): void
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
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$haircut->id],
            'products' => [$pomade->id => 2],
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'services' => [$wash->id],
            'products' => [],
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-11',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'services' => [],
            'products' => [$gel->id => 1],
        ]);

        Expense::query()->create([
            'expense_date' => '2026-03-10',
            'category' => 'listrik',
            'amount' => '15000.00',
        ]);

        Expense::query()->create([
            'expense_date' => '2026-03-11',
            'category' => 'lainnya',
            'amount' => '10000.00',
        ]);

        Expense::query()->create([
            'expense_date' => '2026-03-12',
            'category' => 'beli alat',
            'amount' => '25000.00',
        ]);

        $report = app(DailyReportService::class)->getDailyReport('2026-03-10', '2026-03-12');
        $rows = $report['rows']->keyBy('report_date');

        $this->assertSame([
            'total_days_in_period' => 3,
            'total_transactions' => 3,
            'service_revenue' => 150000.0,
            'product_revenue' => 70000.0,
            'total_revenue' => 220000.0,
            'cash' => 140000.0,
            'qr' => 80000.0,
            'expenses' => 50000.0,
            'net_income' => 170000.0,
        ], $report['summary']);

        $this->assertSame([
            'report_date' => '2026-03-10',
            'total_transactions' => 2,
            'service_revenue' => 150000.0,
            'product_revenue' => 40000.0,
            'total_revenue' => 190000.0,
            'cash' => 140000.0,
            'qr' => 50000.0,
            'expenses' => 15000.0,
            'net_income' => 175000.0,
        ], $rows->get('2026-03-10'));

        $this->assertSame([
            'report_date' => '2026-03-11',
            'total_transactions' => 1,
            'service_revenue' => 0.0,
            'product_revenue' => 30000.0,
            'total_revenue' => 30000.0,
            'cash' => 0.0,
            'qr' => 30000.0,
            'expenses' => 10000.0,
            'net_income' => 20000.0,
        ], $rows->get('2026-03-11'));

        $this->assertSame([
            'report_date' => '2026-03-12',
            'total_transactions' => 0,
            'service_revenue' => 0.0,
            'product_revenue' => 0.0,
            'total_revenue' => 0.0,
            'cash' => 0.0,
            'qr' => 0.0,
            'expenses' => 25000.0,
            'net_income' => -25000.0,
        ], $rows->get('2026-03-12'));

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.daily', [
                'tanggal_awal' => '2026-03-10',
                'tanggal_akhir' => '2026-03-12',
            ]));

        $response->assertOk();
        $response->assertSeeText('Pendapatan layanan');
        $response->assertSeeText('Pendapatan produk');
        $response->assertSeeText('Pendapatan bersih');
        $response->assertSeeText(Carbon::parse('2026-03-10')->locale('id')->translatedFormat('d M Y'));
        $response->assertSeeText(Carbon::parse('2026-03-12')->locale('id')->translatedFormat('d M Y'));
        $response->assertSeeText('Rp 220.000');
        $response->assertSeeText('Rp 140.000');
        $response->assertSeeText('Rp 80.000');
        $response->assertSeeText('Rp 50.000');
        $response->assertSeeText('Rp 170.000');
        $response->assertSeeText('Rp -25.000');
        $response->assertDontSeeText('Ringkasan laporan');
        $response->assertDontSeeText('Total hari pada periode');
    }

    public function test_daily_report_rejects_invalid_date_range(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->from(route('reports.daily'))
            ->get(route('reports.daily', [
                'tanggal_awal' => '2026-03-12',
                'tanggal_akhir' => '2026-03-10',
            ]));

        $response->assertRedirect(route('reports.daily'));
        $response->assertSessionHasErrors([
            'tanggal_awal' => 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.',
        ]);
    }

    public function test_daily_report_shows_empty_state_when_period_has_no_data(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.daily', [
                'tanggal_awal' => '2026-03-01',
                'tanggal_akhir' => '2026-03-03',
            ]));

        $response->assertOk();
        $response->assertSeeText('Belum ada data pada periode ini');
        $response->assertSeeText('Tidak ada transaksi atau pengeluaran yang tercatat');
    }

    public function test_daily_report_can_export_csv_using_active_filters(): void
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
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [$service->id],
            'products' => [$product->id => 1],
        ]);

        Expense::query()->create([
            'expense_date' => '2026-03-10',
            'category' => 'listrik',
            'amount' => '15000.00',
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-20',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'services' => [$service->id],
            'products' => [],
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.daily.export.csv', [
                'tanggal_awal' => '2026-03-10',
                'tanggal_akhir' => '2026-03-10',
            ]));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=laporan-harian-2026-03-10_sampai_2026-03-10.csv');

        $csv = $this->parseCsv($response->streamedContent());

        $this->assertSame([
            ['Tanggal', 'Jumlah transaksi', 'Pendapatan layanan', 'Pendapatan produk', 'Total pendapatan', 'Cash', 'QR', 'Pengeluaran', 'Pendapatan bersih'],
            ['2026-03-10', '1', '100000', '20000', '120000', '120000', '0', '15000', '105000'],
            ['Total', '1', '100000', '20000', '120000', '120000', '0', '15000', '105000'],
        ], $csv);
    }

    private function parseCsv(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = preg_split("/\r\n|\n|\r/", trim($content)) ?: [];

        return array_map(fn (string $line): array => str_getcsv($line), $lines);
    }
}
