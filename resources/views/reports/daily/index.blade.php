<x-app-layout>
    <x-slot name="header">
        <x-report-page-header title="Laporan Harian" />
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $tanggalAwal = $tanggalAwal ?? now()->subDays(7)->toDateString();
        $tanggalAkhir = $tanggalAkhir ?? now()->toDateString();
        $periodLabel = \Illuminate\Support\Carbon::parse($tanggalAwal)->locale('id')->translatedFormat('d M Y')
            .' - '.
            \Illuminate\Support\Carbon::parse($tanggalAkhir)->locale('id')->translatedFormat('d M Y');

        $tableRows = $rows->map(fn (array $row) => [
            \Illuminate\Support\Carbon::parse($row['report_date'])->locale('id')->translatedFormat('d M Y'),
            number_format((int) ($row['total_transactions'] ?? 0), 0, ',', '.'),
            format_rupiah($row['service_revenue'] ?? 0),
            format_rupiah($row['product_revenue'] ?? 0),
            format_rupiah($row['total_revenue'] ?? 0),
            format_rupiah($row['cash'] ?? 0),
            format_rupiah($row['qr'] ?? 0),
            format_rupiah($row['expenses'] ?? 0),
            format_rupiah($row['net_income'] ?? 0),
        ])->all();

        $footer = [
            'Total',
            number_format((int) $rows->sum('total_transactions'), 0, ',', '.'),
            format_rupiah($rows->sum('service_revenue')),
            format_rupiah($rows->sum('product_revenue')),
            format_rupiah($rows->sum('total_revenue')),
            format_rupiah($rows->sum('cash')),
            format_rupiah($rows->sum('qr')),
            format_rupiah($rows->sum('expenses')),
            format_rupiah($rows->sum('net_income')),
        ];
    @endphp

    <div class="space-y-6">
        <x-report-filter
            :action="route('reports.daily')"
            :showDateRange="true"
            :showYear="false"
            :startDateField="'tanggal_awal'"
            :endDateField="'tanggal_akhir'"
            :startDate="$tanggalAwal"
            :endDate="$tanggalAkhir"
            :filterKeys="['tanggal_awal', 'tanggal_akhir']"
        />

        @if ($rows->isNotEmpty())
            <x-report-table
                :headers="[
                    'Tanggal',
                    'Jumlah transaksi',
                    'Pendapatan layanan',
                    'Pendapatan produk',
                    'Total pendapatan',
                    'Cash',
                    'QR',
                    'Pengeluaran',
                    'Pendapatan bersih',
                ]"
                :rows="$tableRows"
                :footer="$footer"
            />
        @else
            <section class="admin-card">
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                    <h3 class="text-base font-semibold text-slate-900">Belum ada data pada periode ini</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Tidak ada transaksi atau pengeluaran yang tercatat untuk {{ $periodLabel }}.
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        Ubah filter tanggal untuk melihat rekap harian pada periode lain.
                    </p>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
