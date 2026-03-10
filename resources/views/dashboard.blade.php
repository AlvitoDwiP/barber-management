<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Dashboard') }}</h2>
    </x-slot>

    <div class="space-y-6">
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
