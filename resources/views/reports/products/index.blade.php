<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Laporan Produk</h2>
    </x-slot>

    <div class="space-y-6">
        <x-report-filter :action="route('reports.products')" :showDateRange="true" :showYear="true" />

        <x-report-table
            :headers="['Produk', 'Qty Terjual', 'Total Penjualan']"
            :rows="[]"
            empty-message="Data laporan produk belum tersedia."
        />
    </div>
</x-app-layout>
