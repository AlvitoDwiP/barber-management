<x-app-layout>
    <x-slot name="header">
        <x-report-page-header title="Laporan Bulanan" />
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
            (float) ($row['employee_commissions'] ?? 0),
            (float) ($row['expenses'] ?? 0),
        ])->contains(fn (float $value): bool => abs($value) > 0.0));
    @endphp

    <div class="space-y-6">
        <x-report-filter :action="route('reports.monthly')" :showDateRange="false" :showYear="false" :filterKeys="['year']">
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
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
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

        <p class="px-1 text-xs uppercase tracking-wide text-slate-500">Menampilkan rekap tahun {{ $year }}</p>

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
                        Pilih tahun lain untuk melihat rekap performa bisnis per bulan.
                    </p>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
