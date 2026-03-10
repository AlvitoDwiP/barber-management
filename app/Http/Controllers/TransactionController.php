<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'employee_id' => $request->query('employee_id'),
            'payment_method' => $request->query('payment_method'),
        ];

        $transactions = Transaction::query()
            ->with(['employee:id,name'])
            ->withCount([
                'transactionDetails as total_services' => fn ($query) => $query->where('item_type', 'service'),
                'transactionDetails as total_products' => fn ($query) => $query->where('item_type', 'product'),
            ])
            ->when($filters['start_date'], fn ($query, $startDate) => $query->whereDate('transaction_date', '>=', $startDate))
            ->when($filters['end_date'], fn ($query, $endDate) => $query->whereDate('transaction_date', '<=', $endDate))
            ->when($filters['employee_id'], fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['payment_method'], fn ($query, $paymentMethod) => $query->where('payment_method', $paymentMethod))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $employees = Employee::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('transactions.index', compact('transactions', 'employees', 'filters'));
    }

    public function create(): View
    {
        $employees = Employee::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $services = Service::query()
            ->orderBy('name')
            ->get(['id', 'name', 'price']);

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'stock']);

        return view('transactions.create', compact('employees', 'services', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort(501);
    }

    public function show(string $id): View
    {
        return view('transactions.show', compact('id'));
    }

    public function edit(string $id): View
    {
        return view('transactions.edit', compact('id'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        abort(501);
    }

    public function destroy(string $id): RedirectResponse
    {
        abort(501);
    }
}
