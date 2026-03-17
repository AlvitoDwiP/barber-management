<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\FreelancePaymentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FreelancePayrollController extends Controller
{
    public function index(Request $request, FreelancePaymentService $freelancePaymentService): View
    {
        $filters = $freelancePaymentService->normalizeFilters([
            'start_date' => $request->query('start_date', now()->toDateString()),
            'end_date' => $request->query('end_date', $request->query('start_date', now()->toDateString())),
            'employee_id' => $request->query('employee_id'),
        ]);

        $rows = $freelancePaymentService->getIndexRows($filters);
        $selectedEmployeeId = $filters['employee_id'];
        $employees = Employee::query()
            ->where(function ($query) use ($selectedEmployeeId): void {
                $query
                    ->freelance()
                    ->active();

                if ($selectedEmployeeId !== null) {
                    $query->orWhere(function ($employeeQuery) use ($selectedEmployeeId): void {
                        $employeeQuery
                            ->freelance()
                            ->whereKey($selectedEmployeeId);
                    });
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('payroll.freelance.index', compact('filters', 'rows', 'employees'));
    }

    public function preparePayment(Request $request, FreelancePaymentService $freelancePaymentService): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'work_date' => ['required', 'date'],
        ]);

        try {
            $payment = $freelancePaymentService->preparePayment(
                (int) $validated['employee_id'],
                $validated['work_date'],
            );

            return redirect()->route('expenses.create', ['freelance_payment' => $payment->id]);
        } catch (DomainException $exception) {
            return redirect()
                ->route('payroll.freelance.index')
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('payroll.freelance.index')
                ->with('error', 'Terjadi kesalahan saat menyiapkan pembayaran freelance. Silakan coba lagi.');
        }
    }
}
