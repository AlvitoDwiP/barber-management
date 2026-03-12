<x-app-layout>
    <x-slot name="header">
        <x-report-page-header title="Laporan Harian" />
    </x-slot>

    @php
        $rows = $rows ?? collect();
        $startDate = $startDate ?? now()->subDays(7)->toDateString();
        $endDate = $endDate ?? now()->toDateString();
        $periodLabel = \Illuminate\Support\Carbon::parse($startDate)->locale('id')->translatedFormat('d M Y')
            .' - '.
            \Illuminate\Support\Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y');

        $tableRows = $rows->map(fn ($row) => [
            \Illuminate\Support\Carbon::parse($row->transaction_date)->locale('id')->translatedFormat('d M Y'),
            format_rupiah($row->total_revenue),
            number_format((int) $row->total_transactions, 0, ',', '.'),
            format_rupiah($row->total_cash),
            format_rupiah($row->total_qr),
        ])->all();

        $footer = [
            'Total',
            format_rupiah($rows->sum('total_revenue')),
            number_format((int) $rows->sum('total_transactions'), 0, ',', '.'),
            format_rupiah($rows->sum('total_cash')),
            format_rupiah($rows->sum('total_qr')),
        ];
    @endphp

    <div class="space-y-6">
        <x-report-filter
            :action="route('reports.daily')"
            :showDateRange="true"
            :showYear="false"
            :startDate="$startDate"
            :endDate="$endDate"
            :filterKeys="['start_date', 'end_date']"
        />

        <div class="admin-card">
            <p class="text-xs uppercase tracking-wide text-slate-500">Periode laporan</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $periodLabel }}</p>
        </div>

        <x-report-table
            :headers="['Tanggal', 'Pendapatan', 'Transaksi', 'Cash', 'QR']"
            :rows="$tableRows"
            :footer="$footer"
            empty-message="Belum ada data transaksi pada periode ini."
        />
    </div>
</x-app-layout>
