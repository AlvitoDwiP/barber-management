<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Tambah Transaksi') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="admin-card">
            <div class="mb-6">
                <h3 class="text-base font-semibold text-slate-900">Form Transaksi Baru</h3>
                <p class="text-sm text-slate-500">Isi data transaksi, pilih layanan, lalu tambah produk jika diperlukan.</p>
            </div>

            <form method="POST" action="{{ route('transactions.store') }}" class="space-y-6">
                @csrf

                @include('transactions._partials.form', [
                    'transaction' => null,
                    'employees' => $employees,
                    'services' => $services,
                    'products' => $products,
                    'selectedServices' => $selectedServices,
                    'selectedProducts' => $selectedProducts,
                    'submitLabel' => 'Simpan',
                ])
            </form>
        </div>
    </div>
</x-app-layout>
