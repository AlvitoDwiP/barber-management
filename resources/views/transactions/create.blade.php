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

            @if ($activePayroll)
                <div class="mb-6 rounded-xl border border-[#E1C5B8] bg-[#FAF3EF] p-4 text-sm text-[#7D4026]">
                    <p class="font-semibold">Payroll aktif</p>
                    <p class="mt-1">
                        Periode: {{ $activePayroll->start_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                        sampai
                        {{ $activePayroll->end_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                    </p>
                </div>
            @endif

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
