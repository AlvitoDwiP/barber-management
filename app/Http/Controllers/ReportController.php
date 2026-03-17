<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\Reports\DailyReportService;
use App\Services\Reports\EmployeePerformanceReportService;
use App\Services\Reports\MonthlyReportService;
use App\Services\Reports\PaymentReportService;
use App\Services\Reports\ProductReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function daily(Request $request, DailyReportService $dailyReportService): View
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

        $rows = $dailyReportService->getDailyRevenueReport($tanggalAwal, $tanggalAkhir);

        return view('reports.daily.index', [
            'rows' => $rows,
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
        ]);
    }

    public function monthly(Request $request, MonthlyReportService $monthlyReportService): View
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

        $rows = $monthlyReportService->getMonthlyRevenueReport($year);

        return view('reports.monthly.index', compact('rows', 'year'));
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

    public function products(ProductReportService $productReportService): View
    {
        $validated = request()->validate([
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

        $rows = $productReportService->getProductSalesReport($tanggalAwal, $tanggalAkhir, $produkId);
        $products = $productReportService->getProductsForFilter();

        return view('reports.products.index', [
            'rows' => $rows,
            'products' => $products,
            'produkId' => $produkId,
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
        ]);
    }

    public function employees(Request $request, EmployeePerformanceReportService $employeePerformanceReportService): View
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

        $rows = $employeePerformanceReportService->getEmployeePerformanceReport($tanggalAwal, $tanggalAkhir, $pegawaiId);
        $employees = Employee::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reports.employees.index', [
            'rows' => $rows,
            'employees' => $employees,
            'pegawaiId' => $pegawaiId,
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
        ]);
    }
}
