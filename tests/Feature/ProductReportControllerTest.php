<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\Reports\ProductReportService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_sales_report_shows_sales_recaps_per_product(): void
    {
        $employee = $this->createEmployee();
        $service = Service::query()->create([
            'name' => 'Haircut Premium',
            'price' => '100000.00',
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
            'services' => [$service->id],
            'products' => [$pomade->id => 2, $gel->id => 1],
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-11',
            'employee_id' => $employee->id,
            'payment_method' => 'qr',
            'services' => [],
            'products' => [$pomade->id => 1],
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-02-20',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'services' => [],
            'products' => [$gel->id => 2],
        ]);

        $rows = app(ProductReportService::class)
            ->getProductSalesReport('2026-03-10', '2026-03-11')
            ->keyBy('product_name');

        $this->assertSame([
            'product_name' => 'Pomade',
            'total_qty_sold' => 3,
            'average_selling_price' => 20000.0,
            'total_revenue' => 60000.0,
        ], $rows->get('Pomade'));

        $this->assertSame([
            'product_name' => 'Gel',
            'total_qty_sold' => 1,
            'average_selling_price' => 30000.0,
            'total_revenue' => 30000.0,
        ], $rows->get('Gel'));

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.products', [
                'tanggal_awal' => '2026-03-10',
                'tanggal_akhir' => '2026-03-11',
            ]));

        $response->assertOk();
        $response->assertSeeText('Laporan Penjualan Produk');
        $response->assertSeeText('Nama produk');
        $response->assertSeeText('Qty terjual');
        $response->assertSeeText('Harga jual rata-rata');
        $response->assertSeeText('Total omzet');
        $response->assertSeeText('Pomade');
        $response->assertSeeText('Gel');
        $response->assertSeeText('Rp 20.000');
        $response->assertSeeText('Rp 30.000');
        $response->assertSeeText('Rp 60.000');
        $response->assertSeeText('Rp 90.000');
        $response->assertDontSeeText('Stok tersisa');
        $response->assertDontSeeText('Stok rendah');
        $response->assertDontSeeText('Laporan Produk');
    }

    public function test_product_sales_report_can_filter_by_product(): void
    {
        $employee = $this->createEmployee();
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
            'services' => [],
            'products' => [$pomade->id => 2, $gel->id => 1],
        ]);

        $rows = app(ProductReportService::class)
            ->getProductSalesReport('2026-03-10', '2026-03-10', $gel->id);

        $this->assertCount(1, $rows);
        $this->assertSame('Gel', $rows->first()['product_name']);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.products', [
                'tanggal_awal' => '2026-03-10',
                'tanggal_akhir' => '2026-03-10',
                'produk_id' => $gel->id,
            ]));

        $response->assertOk();
        $response->assertSeeText('Periode 10 Mar 2026 - 10 Mar 2026');
        $response->assertSeeText('Gel');
        $response->assertSeeText('Rp 30.000');
        $response->assertDontSeeText('Rp 60.000');
    }

    public function test_product_sales_report_handles_invalid_range_and_empty_state(): void
    {
        $product = Product::query()->create([
            'name' => 'Pomade',
            'price' => '20000.00',
            'stock' => 20,
        ]);

        $invalidResponse = $this->actingAs(User::factory()->create())
            ->from(route('reports.products'))
            ->get(route('reports.products', [
                'tanggal_awal' => '2026-03-12',
                'tanggal_akhir' => '2026-03-10',
            ]));

        $invalidResponse->assertRedirect(route('reports.products'));
        $invalidResponse->assertSessionHasErrors([
            'tanggal_awal' => 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.',
        ]);

        $emptyResponse = $this->actingAs(User::factory()->create())
            ->get(route('reports.products', [
                'tanggal_awal' => '2026-03-01',
                'tanggal_akhir' => '2026-03-03',
                'produk_id' => $product->id,
            ]));

        $emptyResponse->assertOk();
        $emptyResponse->assertSeeText('Belum ada data penjualan produk');
        $emptyResponse->assertSeeText('Tidak ada penjualan produk yang tercatat');
    }

    private function createEmployee(): Employee
    {
        return Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);
    }
}
