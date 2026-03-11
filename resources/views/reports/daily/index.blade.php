<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Laporan Harian</h2>
    </x-slot>

    <div class="space-y-6">
        <x-report-filter :action="route('reports.daily')" :showDateRange="true" :showYear="false" />

        <x-report-table
            :headers="['Tanggal', 'Jumlah Transaksi', 'Total Pendapatan']"
            :rows="[]"
            empty-message="Data laporan harian belum tersedia."
        />
    </div>
</x-app-layout>
