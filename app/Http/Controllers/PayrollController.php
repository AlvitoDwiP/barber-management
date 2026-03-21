<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayrollPeriodRequest;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(PayrollService $payrollService): View
    {
        $payrollPeriods = PayrollPeriod::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(15);
        $payrollOverlapWarning = (bool) session('payroll_overlap_warning', false);
        $transactionCountsByPayrollId = [];

        foreach ($payrollPeriods->getCollection()->where('status', PayrollPeriod::STATUS_OPEN) as $period) {
            $transactionCountsByPayrollId[$period->id] = $payrollService->countPendingTransactionsForPeriod($period);
        }

        return view('payroll.index', compact('payrollPeriods', 'payrollOverlapWarning', 'transactionCountsByPayrollId'));
    }

    public function show(PayrollPeriod $payroll, PayrollService $payrollService): View
    {
        $payrollPeriod = $payroll->fresh();
        $payrollRows = $payrollService->getPayrollDisplayRows($payrollPeriod);
        $transactionCount = $payrollPeriod->status === PayrollPeriod::STATUS_OPEN
            ? $payrollService->countPendingTransactionsForPeriod($payrollPeriod)
            : 0;

        return view('payroll.show', compact('payrollPeriod', 'payrollRows', 'transactionCount'));
    }

    public function open(StorePayrollPeriodRequest $request, PayrollService $payrollService): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $startDate = $validated['start_date'];
            $endDate = $validated['end_date'];

            if (PayrollPeriod::query()->where('status', PayrollPeriod::STATUS_OPEN)->exists()) {
                return redirect()
                    ->route('payroll.index')
                    ->withInput()
                    ->with('error', 'Masih ada payroll yang belum ditutup.');
            }

            $isOverlap = $payrollService->hasOverlapPeriod($startDate, $endDate);
            $isConfirmed = $request->boolean('overlap_confirmation');

            if ($isOverlap && ! $isConfirmed) {
                return redirect()
                    ->route('payroll.index')
                    ->withInput()
                    ->with('payroll_overlap_warning', true)
                    ->with('error', 'Periode payroll overlap dengan payroll lain. Silakan konfirmasi untuk melanjutkan.');
            }

            $payrollService->openPayroll($startDate, $endDate);

            return redirect()
                ->route('payroll.index')
                ->with('success', 'Periode payroll berhasil dibuka.');
        } catch (DomainException $exception) {
            return redirect()
                ->route('payroll.index')
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('payroll.index')
                ->with('error', 'Terjadi kendala saat membuka periode payroll. Silakan coba lagi.');
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
