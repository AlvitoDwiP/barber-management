<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Dashboard</h2>
    </x-slot>

    @php
        $todaySummary = $todaySummary ?? [];
        $monthlySummary = $monthlySummary ?? [];
        $topEmployee = $topEmployee ?? null;
        $topProduct = $topProduct ?? null;
    @endphp

    <div class="space-y-6">
        <section class="admin-card space-y-3">
            <h3 class="text-base font-semibold text-slate-900">Ringkasan hari ini</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Tanggal</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $todaySummary['date'] ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total transaksi</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $todaySummary['total_transactions'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total pendapatan</p>
                    <p class="mt-1 font-medium text-slate-900">Rp {{ number_format((float) ($todaySummary['total_revenue'] ?? 0), 0, ',', '.') }}</p>
                </div>
            </div>
        </section>

        <section class="admin-card space-y-3">
            <h3 class="text-base font-semibold text-slate-900">Ringkasan bulan ini</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Periode</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $monthlySummary['month'] ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total transaksi</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $monthlySummary['total_transactions'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total pendapatan</p>
                    <p class="mt-1 font-medium text-slate-900">Rp {{ number_format((float) ($monthlySummary['total_revenue'] ?? 0), 0, ',', '.') }}</p>
                </div>
            </div>
        </section>

        <section class="admin-card space-y-3">
            <h3 class="text-base font-semibold text-slate-900">Statistik bisnis</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Top pegawai bulan ini</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $topEmployee['employee_name'] ?? 'Belum ada data' }}</p>
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Top produk bulan ini</p>
                    <p class="mt-1 font-medium text-slate-900">{{ $topProduct['product_name'] ?? 'Belum ada data' }}</p>
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
                    <p class="mt-1 font-medium text-slate-900">Placeholder metric</p>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
