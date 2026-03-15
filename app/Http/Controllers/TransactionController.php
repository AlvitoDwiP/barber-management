<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyBatchTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
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
            'payment_method' => $request->query('payment_method'),
        ];

        $transactions = Transaction::query()
            ->with(['employee:id,name'])
            ->withSum(['transactionItems as total_services' => fn ($query) => $query->where('item_type', 'service')], 'qty')
            ->withSum(['transactionItems as total_products' => fn ($query) => $query->where('item_type', 'product')], 'qty')
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

    public function createDailyBatch(): View
    {
        ['employees' => $employees, 'services' => $services, 'products' => $products] = $this->getTransactionFormOptions();
        $activePayroll = PayrollPeriod::query()
            ->where('status', PayrollPeriod::STATUS_OPEN)
            ->latest('id')
            ->first();

        return view('transactions.daily-batch', compact('employees', 'services', 'products', 'activePayroll'));
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
                'transactionItems' => fn ($query) => $query->orderBy('id'),
            ])
            ->findOrFail($id);

        return view('transactions.show', compact('transaction'));
    }

    public function edit(string $id): View
    {
        $transaction = Transaction::query()
            ->with([
                'transactionItems' => fn ($query) => $query
                    ->select('id', 'transaction_id', 'item_type', 'service_id', 'product_id', 'qty')
                    ->orderBy('id'),
            ])
            ->findOrFail($id);

        ['employees' => $employees, 'services' => $services, 'products' => $products] = $this->getTransactionFormOptions();
        ['selectedServices' => $selectedServices, 'selectedProducts' => $selectedProducts] = $this->mapTransactionSelections($transaction);

        return view('transactions.edit', compact(
            'transaction',
            'employees',
            'services',
            'products',
            'selectedServices',
            'selectedProducts',
        ));
    }

    public function update(
        UpdateTransactionRequest $request,
        Transaction $transaction,
        TransactionService $transactionService
    ): RedirectResponse
    {
        try {
            $transaction = $transactionService->updateTransaction($transaction, $request->validated());

            return redirect()
                ->route('transactions.show', $transaction)
                ->with('success', 'Transaksi berhasil diperbarui.');
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

    private function getTransactionFormOptions(): array
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

        return compact('employees', 'services', 'products');
    }

    private function mapTransactionSelections(Transaction $transaction): array
    {
        $selectedServices = $transaction->transactionItems
            ->where('item_type', 'service')
            ->filter(fn ($detail) => $detail->service_id !== null && (int) $detail->qty > 0)
            ->flatMap(fn ($detail) => array_fill(0, max(1, (int) $detail->qty), [
                'service_id' => (int) $detail->service_id,
            ]))
            ->values()
            ->all();

        $selectedProducts = $transaction->transactionItems
            ->where('item_type', 'product')
            ->filter(fn ($detail) => $detail->product_id !== null && (int) $detail->qty > 0)
            ->map(fn ($detail) => [
                'product_id' => (int) $detail->product_id,
                'qty' => (int) $detail->qty,
            ])
            ->all();

        return compact('selectedServices', 'selectedProducts');
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
