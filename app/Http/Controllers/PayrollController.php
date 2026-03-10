<?php

namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use DomainException;
use Illuminate\Http\RedirectResponse;
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

    public function open(PayrollService $payrollService): RedirectResponse
    {
        try {
            $payrollService->openPayroll();

            return redirect()
                ->route('payroll.index')
                ->with('success', 'Payroll period berhasil dibuka.');
        } catch (DomainException $exception) {
            return redirect()
                ->route('payroll.index')
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('payroll.index')
                ->with('error', 'Terjadi kesalahan saat membuka payroll period. Silakan coba lagi.');
        }
    }
}
