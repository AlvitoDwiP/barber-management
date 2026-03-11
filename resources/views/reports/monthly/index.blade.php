<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Laporan Bulanan</h2>
    </x-slot>

    <div class="space-y-6">
        <x-report-filter :action="route('reports.monthly')" :showDateRange="false" :showYear="true" />

        <x-report-table
            :headers="['Bulan', 'Jumlah Transaksi', 'Total Pendapatan']"
            :rows="[]"
            empty-message="Data laporan bulanan belum tersedia."
        />
    </div>
</x-app-layout>
