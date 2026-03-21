<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\Reports\CsvExportService;
use App\Services\Reports\DailyReportService;
use App\Services\Reports\EmployeePerformanceReportService;
use App\Services\Reports\MonthlyReportService;
use App\Services\Reports\PaymentReportService;
use App\Services\Reports\ProductReportService;
use App\Services\Reports\ReportTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function daily(
        Request $request,
        DailyReportService $dailyReportService,
        ReportTableService $reportTableService
    ): View
    {
        ['tanggal_awal' => $tanggalAwal, 'tanggal_akhir' => $tanggalAkhir] = $this->resolveDailyFilters($request);

        $rows = $dailyReportService->getDailyRevenueReport($tanggalAwal, $tanggalAkhir);

        return view('reports.daily.index', [
            'rows' => $rows,
            'table' => $reportTableService->buildDailyTable($rows),
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
        ]);
    }

    public function exportDailyCsv(
        Request $request,
        DailyReportService $dailyReportService,
        ReportTableService $reportTableService,
        CsvExportService $csvExportService
    ): StreamedResponse {
        ['tanggal_awal' => $tanggalAwal, 'tanggal_akhir' => $tanggalAkhir] = $this->resolveDailyFilters($request);

        $rows = $dailyReportService->getDailyRevenueReport($tanggalAwal, $tanggalAkhir);
        $table = $reportTableService->buildDailyTable($rows);

        return $csvExportService->download(
            "laporan-harian-{$tanggalAwal}-sampai-{$tanggalAkhir}.csv",
            $table['headers'],
            $table['csvRows'],
            $table['csvFooter'],
        );
    }

    public function monthly(
        Request $request,
        MonthlyReportService $monthlyReportService,
        ReportTableService $reportTableService
    ): View
    {
        ['year' => $year] = $this->resolveMonthlyFilters($request);

        $rows = $monthlyReportService->getMonthlyRevenueReport($year);

        return view('reports.monthly.index', [
            'rows' => $rows,
            'table' => $reportTableService->buildMonthlyTable($rows, $year),
            'year' => $year,
        ]);
    }

    public function exportMonthlyCsv(
        Request $request,
        MonthlyReportService $monthlyReportService,
        ReportTableService $reportTableService,
        CsvExportService $csvExportService
    ): StreamedResponse {
        ['year' => $year] = $this->resolveMonthlyFilters($request);

        $rows = $monthlyReportService->getMonthlyRevenueReport($year);
        $table = $reportTableService->buildMonthlyTable($rows, $year);

        return $csvExportService->download(
            "laporan-bulanan-{$year}.csv",
            $table['headers'],
            $table['csvRows'],
            $table['csvFooter'],
        );
    }

    public function payment(Request $request, PaymentReportService $paymentReportService): View
    {
        $validated = $request->validate([
            'year' => ['nullable', 'regex:/^\d{4}$/'],
        ], [
            'year.regex' => 'Tahun tidak valid.',
        ]);

        $currentYear = now()->year;
        $year = isset($validated['year']) ? (int) $validated['year'] : $currentYear;

        if (abs($year - $currentYear) > 10) {
            throw ValidationException::withMessages([
                'year' => 'Tahun tidak valid.',
            ]);
        }

        $rows = $paymentReportService->getPaymentMethodReport($year);

        return view('reports.payment.index', compact('rows', 'year'));
    }

    public function products(
        Request $request,
        ProductReportService $productReportService,
        ReportTableService $reportTableService
    ): View
    {
        [
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'produk_id' => $produkId,
        ] = $this->resolveProductFilters($request);

        $rows = $productReportService->getProductSalesReport($tanggalAwal, $tanggalAkhir, $produkId);
        $products = $productReportService->getProductsForFilter();

        return view('reports.products.index', [
            'rows' => $rows,
            'table' => $reportTableService->buildProductSalesTable($rows),
            'products' => $products,
            'produkId' => $produkId,
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
        ]);
    }

    public function exportProductsCsv(
        Request $request,
        ProductReportService $productReportService,
        ReportTableService $reportTableService,
        CsvExportService $csvExportService
    ): StreamedResponse {
        [
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'produk_id' => $produkId,
        ] = $this->resolveProductFilters($request);

        $rows = $productReportService->getProductSalesReport($tanggalAwal, $tanggalAkhir, $produkId);
        $table = $reportTableService->buildProductSalesTable($rows);
        $productName = $produkId !== null
            ? $productReportService->getProductsForFilter()->firstWhere('id', $produkId)?->name
            : null;

        return $csvExportService->download(
            'laporan-penjualan-produk-'
            .$tanggalAwal.'-sampai-'.$tanggalAkhir
            .$this->buildOptionalFilenameSuffix($productName)
            .'.csv',
            $table['headers'],
            $table['csvRows'],
            $table['csvFooter'],
        );
    }

    public function employees(
        Request $request,
        EmployeePerformanceReportService $employeePerformanceReportService,
        ReportTableService $reportTableService
    ): View
    {
        [
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'pegawai_id' => $pegawaiId,
        ] = $this->resolveEmployeeFilters($request);

        $rows = $employeePerformanceReportService->getEmployeePerformanceReport($tanggalAwal, $tanggalAkhir, $pegawaiId);
        $employees = Employee::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reports.employees.index', [
            'rows' => $rows,
            'table' => $reportTableService->buildEmployeePerformanceTable($rows),
            'employees' => $employees,
            'pegawaiId' => $pegawaiId,
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
        ]);
    }

    public function exportEmployeesCsv(
        Request $request,
        EmployeePerformanceReportService $employeePerformanceReportService,
        ReportTableService $reportTableService,
        CsvExportService $csvExportService
    ): StreamedResponse {
        [
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'pegawai_id' => $pegawaiId,
        ] = $this->resolveEmployeeFilters($request);

        $rows = $employeePerformanceReportService->getEmployeePerformanceReport($tanggalAwal, $tanggalAkhir, $pegawaiId);
        $table = $reportTableService->buildEmployeePerformanceTable($rows);
        $employeeName = $pegawaiId !== null
            ? Employee::query()->find($pegawaiId, ['id', 'name'])?->name
            : null;

        return $csvExportService->download(
            'laporan-kinerja-pegawai-'
            .$tanggalAwal.'-sampai-'.$tanggalAkhir
            .$this->buildOptionalFilenameSuffix($employeeName)
            .'.csv',
            $table['headers'],
            $table['csvRows'],
            $table['csvFooter'],
        );
    }

    private function resolveDailyFilters(Request $request): array
    {
        $validated = $request->validate([
            'tanggal_awal' => ['nullable', 'date', 'date_format:Y-m-d'],
            'tanggal_akhir' => ['nullable', 'date', 'date_format:Y-m-d'],
        ], [
            'tanggal_awal.date' => 'Tanggal awal tidak valid.',
            'tanggal_akhir.date' => 'Tanggal akhir tidak valid.',
            'tanggal_awal.date_format' => 'Tanggal awal tidak valid.',
            'tanggal_akhir.date_format' => 'Tanggal akhir tidak valid.',
        ]);

        $tanggalAwal = $validated['tanggal_awal'] ?? now()->subDays(7)->toDateString();
        $tanggalAkhir = $validated['tanggal_akhir'] ?? now()->toDateString();

        $this->ensureDateRangeIsValid($tanggalAwal, $tanggalAkhir);

        return [
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
        ];
    }

    private function resolveMonthlyFilters(Request $request): array
    {
        $validated = $request->validate([
            'year' => ['nullable', 'regex:/^\d{4}$/'],
        ], [
            'year.regex' => 'Tahun tidak valid.',
        ]);

        $currentYear = now()->year;
        $year = isset($validated['year']) ? (int) $validated['year'] : $currentYear;

        if (abs($year - $currentYear) > 10) {
            throw ValidationException::withMessages([
                'year' => 'Tahun tidak valid.',
            ]);
        }

        return ['year' => $year];
    }

    private function resolveProductFilters(Request $request): array
    {
        $validated = $request->validate([
            'tanggal_awal' => ['nullable', 'date', 'date_format:Y-m-d'],
            'tanggal_akhir' => ['nullable', 'date', 'date_format:Y-m-d'],
            'produk_id' => ['nullable', 'integer', 'exists:products,id'],
        ], [
            'tanggal_awal.date' => 'Tanggal awal tidak valid.',
            'tanggal_akhir.date' => 'Tanggal akhir tidak valid.',
            'tanggal_awal.date_format' => 'Tanggal awal tidak valid.',
            'tanggal_akhir.date_format' => 'Tanggal akhir tidak valid.',
            'produk_id.integer' => 'Produk tidak valid.',
            'produk_id.exists' => 'Produk tidak valid.',
        ]);

        $tanggalAwal = $validated['tanggal_awal'] ?? now()->startOfMonth()->toDateString();
        $tanggalAkhir = $validated['tanggal_akhir'] ?? now()->toDateString();
        $produkId = isset($validated['produk_id']) ? (int) $validated['produk_id'] : null;

        $this->ensureDateRangeIsValid($tanggalAwal, $tanggalAkhir);

        return [
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'produk_id' => $produkId,
        ];
    }

    private function resolveEmployeeFilters(Request $request): array
    {
        $validated = $request->validate([
            'tanggal_awal' => ['nullable', 'date', 'date_format:Y-m-d'],
            'tanggal_akhir' => ['nullable', 'date', 'date_format:Y-m-d'],
            'pegawai_id' => ['nullable', 'integer', 'exists:employees,id'],
        ], [
            'tanggal_awal.date' => 'Tanggal awal tidak valid.',
            'tanggal_akhir.date' => 'Tanggal akhir tidak valid.',
            'tanggal_awal.date_format' => 'Tanggal awal tidak valid.',
            'tanggal_akhir.date_format' => 'Tanggal akhir tidak valid.',
            'pegawai_id.integer' => 'Pegawai tidak valid.',
            'pegawai_id.exists' => 'Pegawai tidak valid.',
        ]);

        $tanggalAwal = $validated['tanggal_awal'] ?? now()->startOfMonth()->toDateString();
        $tanggalAkhir = $validated['tanggal_akhir'] ?? now()->toDateString();
        $pegawaiId = isset($validated['pegawai_id']) ? (int) $validated['pegawai_id'] : null;

        $this->ensureDateRangeIsValid($tanggalAwal, $tanggalAkhir);

        return [
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'pegawai_id' => $pegawaiId,
        ];
    }

    private function ensureDateRangeIsValid(string $tanggalAwal, string $tanggalAkhir): void
    {
        $start = Carbon::parse($tanggalAwal);
        $end = Carbon::parse($tanggalAkhir);

        if ($start->gt($end)) {
            throw ValidationException::withMessages([
                'tanggal_awal' => 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.',
            ]);
        }

        if ($start->diffInDays($end) > 365) {
            throw ValidationException::withMessages([
                'tanggal_awal' => 'Rentang laporan maksimal 365 hari.',
            ]);
        }
    }

    private function buildOptionalFilenameSuffix(?string $label): string
    {
        if (! filled($label)) {
            return '';
        }

        $slug = Str::slug($label);

        return $slug !== '' ? '-'.$slug : '';
    }
}
