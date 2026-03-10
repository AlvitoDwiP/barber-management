<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(): View
    {
        $payrollPeriods = PayrollPeriod::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('payroll.index', compact('payrollPeriods'));
    }

    public function show(PayrollPeriod $payroll): View
    {
        $payrollPeriod = $payroll;

        return view('payroll.show', compact('payrollPeriod'));
    }
}
