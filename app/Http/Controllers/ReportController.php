<?php

namespace App\Http\Controllers;

use App\Services\Reports\DailyReportService;
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
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d'],
        ], [
            'start_date.date' => 'Tanggal tidak valid.',
            'end_date.date' => 'Tanggal tidak valid.',
            'start_date.date_format' => 'Tanggal tidak valid.',
            'end_date.date_format' => 'Tanggal tidak valid.',
        ]);

        $startDate = $validated['start_date'] ?? now()->subDays(7)->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            throw ValidationException::withMessages([
                'start_date' => 'Tanggal tidak valid.',
            ]);
        }

        if ($start->diffInDays($end) > 365) {
            throw ValidationException::withMessages([
                'start_date' => 'Rentang laporan maksimal 365 hari.',
            ]);
        }

        $rows = $dailyReportService->getDailyRevenueReport($startDate, $endDate);

        return view('reports.daily.index', compact('rows', 'startDate', 'endDate'));
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

    public function employees(): View
    {
        return view('reports.employees.index');
    }
}
