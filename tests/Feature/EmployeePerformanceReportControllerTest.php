<?php

namespace Tests\Feature;

use App\Models\CommissionSetting;
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

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employeeOne->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employeeOne->id, [$haircut->id, $wash->id], [$pomade->id => 2]),
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-11',
            'employee_id' => $employeeOne->id,
            'payment_method' => 'qr',
            'items' => $this->transactionItems($employeeOne->id, [], [$gel->id => 1]),
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-12',
            'employee_id' => $employeeTwo->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employeeTwo->id, [$wash->id]),
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-02-20',
            'employee_id' => $employeeTwo->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employeeTwo->id, [$haircut->id]),
        ]);

        $rows = app(EmployeePerformanceReportService::class)
            ->getEmployeePerformanceReport('2026-03-10', '2026-03-12')
            ->keyBy('employee_name');

        $this->assertSame([
            'employee_name' => 'Budi',
            'total_transactions' => 2,
            'total_services' => 2,
            'service_revenue' => '150000.00',
            'total_products' => 3,
            'product_revenue' => '70000.00',
            'total_commission' => '90000.00',
        ], $rows->get('Budi'));

        $this->assertSame([
            'employee_name' => 'Sari',
            'total_transactions' => 1,
            'total_services' => 1,
            'service_revenue' => '50000.00',
            'total_products' => 0,
            'product_revenue' => '0.00',
            'total_commission' => '25000.00',
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

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employeeOne->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employeeOne->id, [$haircut->id]),
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employeeTwo->id,
            'payment_method' => 'qr',
            'items' => $this->transactionItems($employeeTwo->id, [$wash->id]),
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

    public function test_employee_performance_report_can_export_csv_with_employee_filter(): void
    {
        [$employeeOne, $employeeTwo] = $this->createEmployees();
        [$haircut, $wash] = array_slice($this->createItems(), 0, 2);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employeeOne->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employeeOne->id, [$haircut->id]),
        ]);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employeeTwo->id,
            'payment_method' => 'qr',
            'items' => $this->transactionItems($employeeTwo->id, [$wash->id]),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.employees.export.csv', [
                'tanggal_awal' => '2026-03-10',
                'tanggal_akhir' => '2026-03-10',
                'pegawai_id' => $employeeTwo->id,
            ]));

        $response->assertOk();
        $response->assertHeader(
            'content-disposition',
            'attachment; filename=laporan-kinerja-pegawai-2026-03-10_sampai_2026-03-10-sari.csv'
        );

        $csv = $this->parseCsv($response->streamedContent());

        $this->assertSame([
            ['Nama pegawai', 'Jumlah transaksi', 'Jumlah layanan dikerjakan', 'Omzet layanan', 'Jumlah produk terjual', 'Omzet produk', 'Total komisi'],
            ['Sari', '1', '1', '50000', '0', '0', '25000'],
            ['Total', '1', '1', '50000', '0', '0', '25000'],
        ], $csv);
    }

    public function test_employee_performance_report_stays_historical_after_master_commission_changes(): void
    {
        [$employee] = $this->createEmployees();
        [$haircut, , $pomade] = array_slice($this->createItems(), 0, 3);

        app(TransactionService::class)->recordTransaction([
            'transaction_date' => '2026-03-10',
            'employee_id' => $employee->id,
            'payment_method' => 'cash',
            'items' => $this->transactionItems($employee->id, [$haircut->id], [$pomade->id => 2]),
        ]);

        $haircut->update([
            'price' => '250000.00',
            'commission_type' => 'percent',
            'commission_value' => '10.00',
        ]);
        $pomade->update([
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

        $row = app(EmployeePerformanceReportService::class)
            ->getEmployeePerformanceReport('2026-03-10', '2026-03-10', $employee->id)
            ->first();

        $this->assertSame('Budi', $row['employee_name']);
        $this->assertSame(1, $row['total_transactions']);
        $this->assertSame(1, $row['total_services']);
        $this->assertSame('100000.00', $row['service_revenue']);
        $this->assertSame(2, $row['total_products']);
        $this->assertSame('40000.00', $row['product_revenue']);
        $this->assertSame('60000.00', $row['total_commission']);
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

    private function parseCsv(string $content): array
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = preg_split("/\r\n|\n|\r/", trim($content)) ?: [];

        return array_map(fn (string $line): array => str_getcsv($line), $lines);
    }
}
