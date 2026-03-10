<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Edit Transaksi') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="admin-card">
            <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Perbarui Data Transaksi</h3>
                    <p class="text-sm text-slate-500">Kode transaksi: {{ $transaction->transaction_code }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('transactions.update', $transaction) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @include('transactions._partials.form', [
                    'transaction' => $transaction,
                    'employees' => $employees,
                    'services' => $services,
                    'products' => $products,
                    'selectedServices' => $selectedServices,
                    'selectedProducts' => $selectedProducts,
                    'submitLabel' => 'Perbarui',
                ])
            </form>
        </div>
    </div>
</x-app-layout>
