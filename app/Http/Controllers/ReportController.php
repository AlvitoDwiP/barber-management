<?php

namespace App\Http\Controllers;

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
        $rows = $productReportService->getProductSalesReport();

        return view('reports.products.index', compact('rows'));
    }

    public function employees(Request $request, EmployeePerformanceReportService $employeePerformanceReportService): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'regex:/^\d{4}$/'],
        ], [
            'month.integer' => 'Bulan tidak valid.',
            'month.between' => 'Bulan tidak valid.',
            'year.regex' => 'Tahun tidak valid.',
        ]);

        $currentDate = now();
        $month = isset($validated['month']) ? (int) $validated['month'] : (int) $currentDate->month;
        $year = isset($validated['year']) ? (int) $validated['year'] : (int) $currentDate->year;

        if (abs($year - (int) $currentDate->year) > 10) {
            throw ValidationException::withMessages([
                'year' => 'Tahun tidak valid.',
            ]);
        }

        $rows = $employeePerformanceReportService->getEmployeePerformanceReport($month, $year);

        return view('reports.employees.index', compact('rows', 'month', 'year'));
    }
}
