<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Laporan Metode Pembayaran</h2>
    </x-slot>

    <div class="space-y-6">
        <x-report-filter :action="route('reports.payment')" :showDateRange="true" :showYear="true" />

        <x-report-table
            :headers="['Periode', 'Cash', 'QR', 'Jumlah Transaksi']"
            :rows="[]"
            empty-message="Data laporan metode pembayaran belum tersedia."
        />
    </div>
</x-app-layout>
