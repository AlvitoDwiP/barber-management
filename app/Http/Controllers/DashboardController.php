<?php

namespace App\Http\Controllers;

use App\Services\Reports\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(ReportService $reportService): View
    {
        $todaySummary = $reportService->getTodaySummary();
        $monthlySummary = $reportService->getMonthlySummary();
        $topEmployee = $reportService->getTopEmployeeOfMonth();
        $topProduct = $reportService->getTopProductOfMonth();

        return view('dashboard.index', compact(
            'todaySummary',
            'monthlySummary',
            'topEmployee',
            'topProduct',
        ));
    }
}
