<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Laporan Harian</h2>
    </x-slot>

    @php
        $rows = $rows ?? collect();
        $startDate = $startDate ?? now()->subDays(7)->toDateString();
        $endDate = $endDate ?? now()->toDateString();

        $tableRows = $rows->map(fn ($row) => [
            \Illuminate\Support\Carbon::parse($row->transaction_date)->locale('id')->translatedFormat('d M Y'),
            'Rp '.number_format((float) $row->total_revenue, 0, ',', '.'),
            number_format((int) $row->total_transactions, 0, ',', '.'),
            'Rp '.number_format((float) $row->total_cash, 0, ',', '.'),
            'Rp '.number_format((float) $row->total_qr, 0, ',', '.'),
        ])->all();

        $footer = [
            'Total',
            'Rp '.number_format((float) $rows->sum('total_revenue'), 0, ',', '.'),
            number_format((int) $rows->sum('total_transactions'), 0, ',', '.'),
            'Rp '.number_format((float) $rows->sum('total_cash'), 0, ',', '.'),
            'Rp '.number_format((float) $rows->sum('total_qr'), 0, ',', '.'),
        ];
    @endphp

    <div class="space-y-6">
        <x-report-filter
            :action="route('reports.daily')"
            :showDateRange="true"
            :showYear="false"
            :startDate="$startDate"
            :endDate="$endDate"
        />

        <x-report-table
            :headers="['Tanggal', 'Pendapatan', 'Transaksi', 'Cash', 'QR']"
            :rows="$tableRows"
            :footer="$footer"
            empty-message="Belum ada data transaksi pada periode ini."
        />
    </div>
</x-app-layout>
