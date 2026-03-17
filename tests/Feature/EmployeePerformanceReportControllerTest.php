<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\Reports\EmployeePerformanceReportService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePerformanceReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_performance_report_shows_employee_contribution_recaps(): void
    {
        [$employeeOne, $employeeTwo] = $this->createEmployees();
        [$haircut, $wash, $pomade, $gel] = $this->createItems();

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employeeOne->id,
            'payment_method' => 'cash',
            'services' => [$haircut->id, $wash->id],
            'products' => [$pomade->id => 2],
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-11',
            'employee_id' => $employeeOne->id,
            'payment_method' => 'qr',
            'services' => [],
            'products' => [$gel->id => 1],
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employeeTwo->id,
            'payment_method' => 'cash',
            'services' => [$wash->id],
            'products' => [],
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-02-20',
            'employee_id' => $employeeTwo->id,
            'payment_method' => 'cash',
            'services' => [$haircut->id],
            'products' => [],
        ]);

        $rows = app(EmployeePerformanceReportService::class)
            ->getEmployeePerformanceReport('2026-03-10', '2026-03-12')
            ->keyBy('employee_name');

        $this->assertSame([
            'employee_name' => 'Budi',
            'total_transactions' => 2,
            'total_services' => 2,
            'service_revenue' => 150000.0,
            'total_products' => 3,
            'product_revenue' => 70000.0,
            'total_commission' => 90000.0,
        ], $rows->get('Budi'));

        $this->assertSame([
            'employee_name' => 'Sari',
            'total_transactions' => 1,
            'total_services' => 1,
            'service_revenue' => 50000.0,
            'total_products' => 0,
            'product_revenue' => 0.0,
            'total_commission' => 25000.0,
        ], $rows->get('Sari'));

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.employees', [
                'tanggal_awal' => '2026-03-10',
                'tanggal_akhir' => '2026-03-12',
            ]));

        $response->assertOk();
        $response->assertSeeText('Laporan Kinerja Pegawai');
        $response->assertSeeText('Nama pegawai');
        $response->assertSeeText('Jumlah transaksi');
        $response->assertSeeText('Jumlah layanan dikerjakan');
        $response->assertSeeText('Omzet layanan');
        $response->assertSeeText('Jumlah produk terjual');
        $response->assertSeeText('Omzet produk');
        $response->assertSeeText('Total komisi');
        $response->assertSeeText('Budi');
        $response->assertSeeText('Sari');
        $response->assertSeeText('Rp 150.000');
        $response->assertSeeText('Rp 70.000');
        $response->assertSeeText('Rp 90.000');
        $response->assertSeeText('Rp 25.000');
        $response->assertDontSeeText('Laporan Produktivitas Pegawai');
    }

    public function test_employee_performance_report_can_filter_by_employee(): void
    {
        [$employeeOne, $employeeTwo] = $this->createEmployees();
        [$haircut, $wash] = array_slice($this->createItems(), 0, 2);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employeeOne->id,
            'payment_method' => 'cash',
            'services' => [$haircut->id],
            'products' => [],
        ]);

        app(TransactionService::class)->storeTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employeeTwo->id,
            'payment_method' => 'qr',
            'services' => [$wash->id],
            'products' => [],
        ]);

        $rows = app(EmployeePerformanceReportService::class)
            ->getEmployeePerformanceReport('2026-03-10', '2026-03-10', $employeeTwo->id);

        $this->assertCount(1, $rows);
        $this->assertSame('Sari', $rows->first()['employee_name']);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.employees', [
                'tanggal_awal' => '2026-03-10',
                'tanggal_akhir' => '2026-03-10',
                'pegawai_id' => $employeeTwo->id,
            ]));

        $response->assertOk();
        $response->assertSeeText('Sari');
        $response->assertSeeText('Periode 10 Mar 2026 - 10 Mar 2026');
        $response->assertSeeText('Rp 50.000');
        $response->assertDontSeeText('Rp 100.000');
    }

    public function test_employee_performance_report_handles_invalid_range_and_empty_state(): void
    {
        $employee = Employee::query()->create([
            'name' => 'Budi',
            'status' => 'tetap',
        ]);

        $invalidResponse = $this->actingAs(User::factory()->create())
            ->from(route('reports.employees'))
            ->get(route('reports.employees', [
                'tanggal_awal' => '2026-03-12',
                'tanggal_akhir' => '2026-03-10',
            ]));

        $invalidResponse->assertRedirect(route('reports.employees'));
        $invalidResponse->assertSessionHasErrors([
            'tanggal_awal' => 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.',
        ]);

        $emptyResponse = $this->actingAs(User::factory()->create())
            ->get(route('reports.employees', [
                'tanggal_awal' => '2026-03-01',
                'tanggal_akhir' => '2026-03-03',
                'pegawai_id' => $employee->id,
            ]));

        $emptyResponse->assertOk();
        $emptyResponse->assertSeeText('Belum ada data kinerja pegawai');
        $emptyResponse->assertSeeText('Tidak ada transaksi pegawai yang tercatat');
    }

    private function createEmployees(): array
    {
        return [
            Employee::query()->create([
                'name' => 'Budi',
                'status' => 'tetap',
            ]),
            Employee::query()->create([
                'name' => 'Sari',
                'status' => 'tetap',
            ]),
        ];
    }

    private function createItems(): array
    {
        return [
            Service::query()->create([
                'name' => 'Haircut Premium',
                'price' => '100000.00',
            ]),
            Service::query()->create([
                'name' => 'Hair Wash',
                'price' => '50000.00',
            ]),
            Product::query()->create([
                'name' => 'Pomade',
                'price' => '20000.00',
                'stock' => 20,
            ]),
            Product::query()->create([
                'name' => 'Gel',
                'price' => '30000.00',
                'stock' => 20,
            ]),
        ];
    }
}
