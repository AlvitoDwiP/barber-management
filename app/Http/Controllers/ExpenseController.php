<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    private const CATEGORIES = [
        'listrik',
        'beli produk stok',
        'beli alat',
        'bayar freelance',
        'lainnya',
    ];

    public function index(): View
    {
        $expenses = Expense::query()
            ->latest('expense_date')
            ->latest('id')
            ->get();

        return view('expenses.index', compact('expenses'));
    }

    public function create(): View
    {
        $categories = self::CATEGORIES;

        return view('expenses.create', compact('categories'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        Expense::query()->create($request->validated());

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Data pengeluaran berhasil ditambahkan.');
    }

    public function show(Expense $expense): RedirectResponse
    {
        return redirect()->route('expenses.edit', $expense);
    }

    public function edit(Expense $expense): View
    {
        $categories = self::CATEGORIES;

        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Data pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Data pengeluaran berhasil dihapus.');
    }
}
