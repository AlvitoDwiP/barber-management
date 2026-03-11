<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Laporan Produktivitas Pegawai</h2>
    </x-slot>

    <div class="space-y-6">
        <x-report-filter :action="route('reports.employees')" :showDateRange="true" :showYear="true" />

        <x-report-table
            :headers="['Pegawai', 'Jumlah Transaksi', 'Total Komisi']"
            :rows="[]"
            empty-message="Data laporan produktivitas pegawai belum tersedia."
        />
    </div>
</x-app-layout>
