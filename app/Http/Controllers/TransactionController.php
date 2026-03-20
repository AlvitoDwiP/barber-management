<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyBatchTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Services\CommissionSettingsService;
use App\Services\TransactionService;
use DomainException;
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
            'payroll_status' => $request->query('payroll_status'),
        ];

        $transactions = Transaction::query()
            ->with([
                'employee:id,name',
                'payrollPeriod:id,status,start_date,end_date,closed_at',
                'transactionItems:id,transaction_id,item_name,item_type,qty,employee_name',
            ])
            ->withSum(
                ['transactionItems as total_services' => fn ($query) => $query->where('item_type', 'service')],
                'qty'
            )
            ->withSum(
                ['transactionItems as total_products' => fn ($query) => $query->where('item_type', 'product')],
                'qty'
            )
            ->when($filters['start_date'], fn ($query, $startDate) => $query->whereDate('transaction_date', '>=', $startDate))
            ->when($filters['end_date'], fn ($query, $endDate) => $query->whereDate('transaction_date', '<=', $endDate))
            ->when($filters['employee_id'], function ($query, $employeeId) {
                $query->where(function ($transactionQuery) use ($employeeId): void {
                    $transactionQuery
                        ->where('employee_id', $employeeId)
                        ->orWhereHas('transactionItems', fn ($itemQuery) => $itemQuery->where('employee_id', $employeeId));
                });
            })
            ->when($filters['payroll_status'], function ($query, $payrollStatus): void {
                match ($payrollStatus) {
                    'open' => $query->whereHas('payrollPeriod', fn ($payrollQuery) => $payrollQuery->where('status', PayrollPeriod::STATUS_OPEN)),
                    'closed' => $query->whereHas('payrollPeriod', fn ($payrollQuery) => $payrollQuery->where('status', PayrollPeriod::STATUS_CLOSED)),
                    'unassigned' => $query->whereNull('payroll_id'),
                    default => null,
                };
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $employees = Employee::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('transactions.index', compact('transactions', 'employees', 'filters'));
    }

    public function createDailyBatch(CommissionSettingsService $commissionSettingsService): View
    {
        ['employees' => $employees, 'services' => $services, 'products' => $products] = $this->getDailyBatchOptions();
        $activePayroll = PayrollPeriod::query()
            ->where('status', PayrollPeriod::STATUS_OPEN)
            ->latest('id')
            ->first();
        $commissionDefaults = [
            'service' => $commissionSettingsService->getDefaultServiceCommission(),
            'product' => $commissionSettingsService->getDefaultProductCommission(),
        ];

        return view('transactions.daily-batch', compact(
            'employees',
            'services',
            'products',
            'activePayroll',
            'commissionDefaults',
        ));
    }

    public function storeDailyBatch(
        StoreDailyBatchTransactionRequest $request,
        TransactionService $transactionService
    ): RedirectResponse {
        try {
            $validated = $request->validated();
            $transactions = $transactionService->storeDailyBatch($validated);
            $closedPayroll = $this->findClosedPayrollForDate($validated['transaction_date']);

            $redirect = redirect()
                ->route('transactions.index')
                ->with('success', $transactions->count().' transaksi berhasil disimpan dari input harian.');

            if ($closedPayroll !== null) {
                $redirect->with('warning', 'Tanggal transaksi ini berada dalam periode payroll yang sudah ditutup. Semua transaksi tetap disimpan tetapi tidak akan mempengaruhi payroll sebelumnya.');
            }

            return $redirect;
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
                ->with('error', 'Terjadi kesalahan saat menyimpan transaksi harian. Silakan coba lagi.');
        }
    }

    public function show(string $id): View
    {
        $transaction = Transaction::query()
            ->with([
                'employee:id,name',
                'payrollPeriod:id,status,start_date,end_date,closed_at',
                'transactionItems' => fn ($query) => $query->orderBy('id'),
            ])
            ->findOrFail($id);

        return view('transactions.show', compact('transaction'));
    }

    public function edit(
        Transaction $transaction,
        CommissionSettingsService $commissionSettingsService
    ): View|RedirectResponse {
        $transaction = $this->loadTransactionForAudit($transaction);

        if ($this->transactionIsLocked($transaction)) {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', 'Transaksi yang sudah terikat ke payroll tertutup tidak dapat diubah atau dihapus.');
        }

        ['employees' => $employees, 'services' => $services, 'products' => $products] = $this->getEditTransactionOptions($transaction);
        $commissionDefaults = [
            'service' => $commissionSettingsService->getDefaultServiceCommission(),
            'product' => $commissionSettingsService->getDefaultProductCommission(),
        ];
        $initialTransaction = [
            'transaction_date' => old('transaction_date', $transaction->transaction_date?->toDateString()),
            'employee_id' => old('employee_id', (string) $transaction->employee_id),
            'payment_method' => old('payment_method', $transaction->payment_method),
            'notes' => old('notes', $transaction->notes),
            'items' => old('items', $transaction->transactionItems->map(fn ($item) => [
                'item_type' => $item->item_type,
                'service_id' => $item->item_type === 'service' ? (string) $item->service_id : '',
                'product_id' => $item->item_type === 'product' ? (string) $item->product_id : '',
                'employee_id' => (string) $item->employee_id,
                'qty' => (int) $item->qty,
            ])->all()),
        ];

        return view('transactions.edit', compact(
            'transaction',
            'employees',
            'services',
            'products',
            'commissionDefaults',
            'initialTransaction',
        ));
    }

    public function update(
        UpdateTransactionRequest $request,
        Transaction $transaction,
        TransactionService $transactionService
    ): RedirectResponse {
        try {
            $updatedTransaction = $transactionService->replaceTransaction($transaction, $request->validated());
            $closedPayroll = $this->findClosedPayrollForDate($updatedTransaction->transaction_date?->toDateString());

            $redirect = redirect()
                ->route('transactions.show', $updatedTransaction)
                ->with('success', 'Transaksi berhasil diperbarui. Snapshot item, total, stok, dan laporan terkait sudah disinkronkan.');

            if ($closedPayroll !== null) {
                $redirect->with('warning', 'Tanggal transaksi yang diperbarui berada dalam periode payroll yang sudah ditutup. Perubahan tetap tersimpan tetapi tidak akan mengubah payroll yang sudah final.');
            }

            return $redirect;
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
                ->with('error', 'Terjadi kesalahan saat memperbarui transaksi. Silakan coba lagi.');
        }
    }

    public function destroy(Transaction $transaction, TransactionService $transactionService): RedirectResponse
    {
        try {
            $transactionService->deleteTransaction($transaction);

            return redirect()
                ->route('transactions.index')
                ->with('success', 'Transaksi berhasil dihapus. Stok produk terkait telah dikembalikan.');
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan saat menghapus transaksi. Silakan coba lagi.');
        }
    }

    private function getDailyBatchOptions(): array
    {
        $employees = Employee::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active', 'employment_type']);

        $services = Service::query()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'commission_type', 'commission_value']);

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'stock', 'commission_type', 'commission_value']);

        return compact('employees', 'services', 'products');
    }

    private function getEditTransactionOptions(Transaction $transaction): array
    {
        $currentEmployeeIds = collect([$transaction->employee_id])
            ->merge($transaction->transactionItems->pluck('employee_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $employees = Employee::query()
            ->where(function ($query) use ($currentEmployeeIds): void {
                $query->where('is_active', true);

                if ($currentEmployeeIds !== []) {
                    $query->orWhereIn('id', $currentEmployeeIds);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'is_active', 'employment_type']);

        $services = Service::query()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'commission_type', 'commission_value']);

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'stock', 'commission_type', 'commission_value']);

        return compact('employees', 'services', 'products');
    }

    private function loadTransactionForAudit(Transaction $transaction): Transaction
    {
        return Transaction::query()
            ->with([
                'employee:id,name',
                'payrollPeriod:id,status,start_date,end_date,closed_at',
                'transactionItems' => fn ($query) => $query->orderBy('id'),
            ])
            ->findOrFail($transaction->id);
    }

    private function transactionIsLocked(Transaction $transaction): bool
    {
        return $transaction->payrollPeriod?->status === PayrollPeriod::STATUS_CLOSED;
    }

    private function findClosedPayrollForDate(?string $transactionDate): ?PayrollPeriod
    {
        if (blank($transactionDate)) {
            return null;
        }

        return PayrollPeriod::query()
            ->where('status', PayrollPeriod::STATUS_CLOSED)
            ->whereDate('start_date', '<=', $transactionDate)
            ->whereDate('end_date', '>=', $transactionDate)
            ->first();
    }
}
