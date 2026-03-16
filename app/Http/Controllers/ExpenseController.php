<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\FreelancePaymentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): View
    {
        $expenses = Expense::query()
            ->with('freelancePayment.employee:id,name')
            ->latest('expense_date')
            ->latest('id')
            ->get();

        return view('expenses.index', compact('expenses'));
    }

    public function create(Request $request, FreelancePaymentService $freelancePaymentService): View|RedirectResponse
    {
        $categories = Expense::categories();
        $freelanceExpenseDraft = null;

        if ($request->filled('freelance_payment')) {
            try {
                $freelanceExpenseDraft = $freelancePaymentService->getExpenseDraft((int) $request->query('freelance_payment'));
            } catch (DomainException $exception) {
                return redirect()
                    ->route('payroll.freelance.index')
                    ->with('error', $exception->getMessage());
            }
        }

        return view('expenses.create', compact('categories', 'freelanceExpenseDraft'));
    }

    public function store(StoreExpenseRequest $request, FreelancePaymentService $freelancePaymentService): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $freelancePaymentId = $validated['freelance_payment_id'] ?? null;

            if ($freelancePaymentId !== null) {
                $freelancePaymentService->settlePaymentWithExpense((int) $freelancePaymentId, $validated);

                return redirect()
                    ->route('payroll.freelance.index')
                    ->with('success', 'Pembayaran freelance berhasil disimpan ke pengeluaran.');
            }

            Expense::query()->create(Arr::except($validated, ['freelance_payment_id']));

            return redirect()
                ->route('expenses.index')
                ->with('success', 'Data pengeluaran berhasil ditambahkan.');
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan pengeluaran. Silakan coba lagi.');
        }
    }

    public function show(Expense $expense): RedirectResponse
    {
        return redirect()->route('expenses.edit', $expense);
    }

    public function edit(Expense $expense): View|RedirectResponse
    {
        try {
            $this->assertExpenseIsEditable($expense);
        } catch (DomainException $exception) {
            return redirect()
                ->route('expenses.index')
                ->with('error', $exception->getMessage());
        }

        $categories = Expense::categories();

        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        try {
            $this->assertExpenseIsEditable($expense);

            $expense->update($request->validated());

            return redirect()
                ->route('expenses.index')
                ->with('success', 'Data pengeluaran berhasil diperbarui.');
        } catch (DomainException $exception) {
            return redirect()
                ->route('expenses.index')
                ->with('error', $exception->getMessage());
        }
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        try {
            $this->assertExpenseIsEditable($expense);

            $expense->delete();

            return redirect()
                ->route('expenses.index')
                ->with('success', 'Data pengeluaran berhasil dihapus.');
        } catch (DomainException $exception) {
            return redirect()
                ->route('expenses.index')
                ->with('error', $exception->getMessage());
        }
    }

    private function assertExpenseIsEditable(Expense $expense): void
    {
        if ($expense->freelancePayment()->exists()) {
            throw new DomainException('Pengeluaran yang berasal dari pembayaran freelance tidak dapat diubah atau dihapus.');
        }
    }
}
