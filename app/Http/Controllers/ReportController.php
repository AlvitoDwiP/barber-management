<?php

namespace App\Http\Controllers;

use App\Services\Reports\DailyReportService;
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

    public function monthly(): View
    {
        return view('reports.monthly.index');
    }

    public function payment(): View
    {
        return view('reports.payment.index');
    }

    public function products(): View
    {
        return view('reports.products.index');
    }

    public function employees(): View
    {
        return view('reports.employees.index');
    }
}
