<x-app-layout>
    <x-slot name="header">
        <x-report-page-header title="Laporan Harian" />
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $table = $table ?? [
            'headers' => [],
            'displayRows' => [],
            'displayFooter' => [],
        ];
        $tanggalAwal = $tanggalAwal ?? now()->subDays(7)->toDateString();
        $tanggalAkhir = $tanggalAkhir ?? now()->toDateString();
        $periodLabel = \Illuminate\Support\Carbon::parse($tanggalAwal)->locale('id')->translatedFormat('d M Y')
            .' - '.
            \Illuminate\Support\Carbon::parse($tanggalAkhir)->locale('id')->translatedFormat('d M Y');
        $exportQuery = [
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
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
        >
            <x-slot name="actions">
                <a href="{{ route('reports.daily.export.csv', $exportQuery) }}" class="btn-neutral-warm shrink-0">
                    Export CSV
                </a>
            </x-slot>
        </x-report-filter>

        @if ($rows->isNotEmpty())
            <x-report-table
                :headers="$table['headers']"
                :rows="$table['displayRows']"
                :footer="$table['displayFooter']"
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
