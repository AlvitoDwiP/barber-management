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
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Pendapatan hari ini</p>
                    <p class="mt-1 font-medium text-slate-900">{{ format_rupiah($todaySummary['today_revenue'] ?? 0) }}</p>
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Jumlah transaksi</p>
                    <p class="mt-1 font-medium text-slate-900">{{ number_format((int) ($todaySummary['today_transactions'] ?? 0), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Cash</p>
                    <p class="mt-1 font-medium text-slate-900">{{ format_rupiah($todaySummary['today_cash'] ?? 0) }}</p>
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">QR</p>
                    <p class="mt-1 font-medium text-slate-900">{{ format_rupiah($todaySummary['today_qr'] ?? 0) }}</p>
                </div>
            </div>
        </section>

        <section class="admin-card space-y-3">
            <h3 class="text-base font-semibold text-slate-900">Ringkasan bulan ini</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Pendapatan bulan ini</p>
                    <p class="mt-1 font-medium text-slate-900">{{ format_rupiah($monthlySummary['month_revenue'] ?? 0) }}</p>
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Pengeluaran bulan ini</p>
                    <p class="mt-1 font-medium text-slate-900">{{ format_rupiah($monthlySummary['month_expenses'] ?? 0) }}</p>
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Estimasi laba bulan ini</p>
                    <p class="mt-1 font-medium text-slate-900">{{ format_rupiah($monthlySummary['month_profit_estimate'] ?? 0) }}</p>
                </div>
            </div>
        </section>

        <section class="admin-card space-y-3">
            <h3 class="text-base font-semibold text-slate-900">Statistik bisnis</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Pegawai paling produktif</p>
                    @if (! empty($topEmployee['employee_name']))
                        <p class="mt-1 font-medium text-slate-900">{{ $topEmployee['employee_name'] }}</p>
                        <p class="text-xs text-slate-500">{{ number_format((int) ($topEmployee['service_count'] ?? 0), 0, ',', '.') }} layanan</p>
                    @else
                        <p class="mt-1 font-medium text-slate-900">Belum ada data</p>
                    @endif
                </div>
                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Produk paling laku</p>
                    @if (! empty($topProduct['product_name']))
                        <p class="mt-1 font-medium text-slate-900">{{ $topProduct['product_name'] }}</p>
                        <p class="text-xs text-slate-500">{{ number_format((int) ($topProduct['qty_sold'] ?? 0), 0, ',', '.') }} qty terjual</p>
                    @else
                        <p class="mt-1 font-medium text-slate-900">Belum ada data</p>
                    @endif
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
