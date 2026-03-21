<x-app-layout>
    <x-slot name="header">
        <x-report-page-header
            title="Laporan Bulanan"
            subtitle="Laporan ini membantu owner membaca performa usaha per bulan dalam satu tahun, dengan istilah yang sama seperti laporan harian."
        />
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $table = $table ?? [
            'headers' => [],
            'displayRows' => [],
            'displayFooter' => [],
        ];
        $year = (int) ($year ?? now()->year);
        $yearOptions = collect(range(now()->year, now()->year - 9));
        $hasData = $rows->contains(fn (array $row): bool => collect([
            (float) ($row['total_revenue'] ?? 0),
            (float) ($row['barber_commissions'] ?? 0),
            (float) ($row['operational_expenses'] ?? 0),
        ])->contains(fn (float $value): bool => abs($value) > 0.0));
        $sumMoney = fn (string $field): string => $rows->reduce(
            fn (string $carry, array $row): string => \App\Support\Money::parse($carry)
                ->add(\App\Support\Money::parse($row[$field] ?? 0))
                ->toDecimal(),
            '0.00',
        );
    @endphp

    <div class="space-y-6">
        <section class="admin-card p-4 sm:p-5">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Komisi barber</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Komisi dihitung sebagai beban operasional saat transaksi terjadi, bukan saat komisi dibayar.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Laba operasional</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Total pendapatan dikurangi komisi barber dan pengeluaran operasional.</p>
                </div>
            </div>
        </section>

        <x-report-filter
            :action="route('reports.monthly')"
            :showDateRange="false"
            :showYear="false"
            :filterKeys="['year']"
            helperText="Pilih tahun untuk melihat rekap per bulan. Tahun yang sama juga dipakai saat export CSV."
        >
            <x-slot name="actions">
                <a href="{{ route('reports.monthly.export.csv', ['year' => $year]) }}" class="btn-neutral-warm shrink-0">
                    Export CSV
                </a>
            </x-slot>

            <div>
                <label for="year" class="text-sm font-medium text-slate-700">Tahun</label>
                <select
                    id="year"
                    name="year"
                    class="form-brand-control"
                >
                    @foreach ($yearOptions as $optionYear)
                        <option value="{{ $optionYear }}" @selected((int) $year === (int) $optionYear)>{{ $optionYear }}</option>
                    @endforeach
                </select>
                @error('year')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </x-report-filter>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Tahun laporan</p>
                <p class="transaction-metric-value">{{ $year }}</p>
            </article>
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Total pendapatan</p>
                <p class="transaction-metric-value">{{ format_rupiah($sumMoney('total_revenue')) }}</p>
            </article>
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Total beban operasional</p>
                <p class="transaction-metric-value">{{ format_rupiah($sumMoney('total_operating_expenses')) }}</p>
            </article>
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Laba operasional</p>
                <p class="transaction-metric-value">{{ format_rupiah($sumMoney('operating_profit')) }}</p>
            </article>
        </section>

        @if ($hasData)
            <x-report-table
                :headers="$table['headers']"
                :rows="$table['displayRows']"
                :footer="$table['displayFooter']"
            />
        @else
            <section class="admin-card">
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                    <h3 class="text-base font-semibold text-slate-900">Belum ada data pada tahun ini</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Belum ada transaksi atau pengeluaran yang tercatat untuk tahun {{ $year }}.
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        Pilih tahun lain atau kembali ke tahun berjalan untuk melihat rekap performa bisnis per bulan.
                    </p>
                    <div class="mt-5 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('reports.monthly') }}" class="btn-neutral-warm">
                            Lihat Tahun Berjalan
                        </a>
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
