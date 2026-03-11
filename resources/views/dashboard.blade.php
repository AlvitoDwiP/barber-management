<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Dashboard') }}</h2>
    </x-slot>

    <div class="space-y-6">
        @if (isset($overdueOpenPayroll) && $overdueOpenPayroll)
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p>
                    Periode payroll
                    {{ $overdueOpenPayroll->start_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                    sampai
                    {{ $overdueOpenPayroll->end_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                    sudah berakhir. Silakan tutup payroll untuk menghitung komisi pegawai.
                </p>
                @if (! is_null($overdueDays))
                    <p class="mt-1 font-medium">
                        Payroll sudah melewati periode selama {{ $overdueDays }} hari.
                    </p>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="admin-card">
                <p class="text-xs uppercase tracking-wide text-slate-500">Ringkasan</p>
                <p class="mt-2 text-sm font-medium text-slate-900">Sistem manajemen studio siap digunakan.</p>
            </div>
            <div class="admin-card">
                <p class="text-xs uppercase tracking-wide text-slate-500">Transaksi</p>
                <p class="mt-2 text-sm font-medium text-slate-900">Pantau transaksi harian dari menu Transaksi.</p>
            </div>
            <div class="admin-card">
                <p class="text-xs uppercase tracking-wide text-slate-500">Produk</p>
                <p class="mt-2 text-sm font-medium text-slate-900">Stok produk dapat dikelola melalui modul Produk.</p>
            </div>
            <div class="admin-card">
                <p class="text-xs uppercase tracking-wide text-slate-500">Laporan</p>
                <p class="mt-2 text-sm font-medium text-slate-900">Akses laporan dan payroll dari sidebar.</p>
            </div>
        </div>

        <div class="admin-card">
            <h3 class="text-base font-semibold text-slate-900">Status</h3>
            <p class="mt-2 text-sm text-slate-600">{{ __("You're logged in!") }}</p>
        </div>
    </div>
</x-app-layout>
