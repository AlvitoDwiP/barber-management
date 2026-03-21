<x-app-layout>
    <x-slot name="header">
        <x-report-page-header
            title="Laporan Harian"
            subtitle="Rekap harian ini merangkum kas masuk, pendapatan, beban operasional, dan laba operasional per tanggal."
        />
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
        $sumMoney = fn (string $field): string => $rows->reduce(
            fn (string $carry, array $row): string => \App\Support\Money::parse($carry)
                ->add(\App\Support\Money::parse($row[$field] ?? 0))
                ->toDecimal(),
            '0.00',
        );
        $summary = [
            'days' => (int) $rows->count(),
            'cash_in' => $sumMoney('cash_in'),
            'total_operating_expenses' => $sumMoney('total_operating_expenses'),
            'operating_profit' => $sumMoney('operating_profit'),
        ];
        $exportQuery = [
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
        ];
    @endphp

    <div class="space-y-6">
        <section class="admin-card p-4 sm:p-5">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kas masuk</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Total pembayaran transaksi yang diterima pada tanggal tersebut.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Laba operasional</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Total pendapatan dikurangi komisi barber dan pengeluaran operasional.</p>
                </div>
            </div>
        </section>

        <x-report-filter
            :action="route('reports.daily')"
            :showDateRange="true"
            :showYear="false"
            :startDateField="'tanggal_awal'"
            :endDateField="'tanggal_akhir'"
            :startDate="$tanggalAwal"
            :endDate="$tanggalAkhir"
            :filterKeys="['tanggal_awal', 'tanggal_akhir']"
            helperText="Atur periode untuk melihat rekap harian. Filter yang sama dipakai saat export CSV."
        >
            <x-slot name="actions">
                <a href="{{ route('reports.daily.export.csv', $exportQuery) }}" class="btn-neutral-warm shrink-0">
                    Export CSV
                </a>
            </x-slot>
        </x-report-filter>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Periode aktif</p>
                <p class="transaction-metric-value">{{ $periodLabel }}</p>
            </article>
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Hari dengan data</p>
                <p class="transaction-metric-value">{{ number_format($summary['days'], 0, ',', '.') }} hari</p>
            </article>
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Kas masuk periode ini</p>
                <p class="transaction-metric-value">{{ format_rupiah($summary['cash_in']) }}</p>
            </article>
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Laba operasional</p>
                <p class="transaction-metric-value">{{ format_rupiah($summary['operating_profit']) }}</p>
            </article>
        </section>

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
                        Ubah filter tanggal, atau kembali ke periode default untuk melihat 7 hari terakhir.
                    </p>
                    <div class="mt-5 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('reports.daily') }}" class="btn-neutral-warm">
                            Lihat Periode Default
                        </a>
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
