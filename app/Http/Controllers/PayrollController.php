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
        $payrollPeriod = $payroll->load([
            'payrollResults.employee',
        ]);

        $payrollPeriod->setRelation(
            'payrollResults',
            $payrollPeriod->payrollResults
                ->sortBy(fn ($result) => mb_strtolower($result->employee?->name ?? ''))
                ->values()
        );

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

    public function close(PayrollPeriod $payroll, PayrollService $payrollService): RedirectResponse
    {
        try {
            $payrollService->closePayroll($payroll);

            return redirect()
                ->route('payroll.show', $payroll)
                ->with('success', 'Payroll berhasil ditutup.');
        } catch (DomainException $exception) {
            return redirect()
                ->route('payroll.show', $payroll)
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('payroll.show', $payroll)
                ->with('error', 'Terjadi kesalahan saat menutup payroll. Silakan coba lagi.');
        }
    }
}
