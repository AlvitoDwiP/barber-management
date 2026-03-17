<x-app-layout>
    <x-slot name="header">
        <x-report-page-header title="Laporan Bulanan" />
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $year = (int) ($year ?? now()->year);
        $yearOptions = collect(range(now()->year, now()->year - 9));
        $hasData = $rows->contains(fn (array $row): bool => collect([
            (float) ($row['total_revenue'] ?? 0),
            (float) ($row['employee_commissions'] ?? 0),
            (float) ($row['expenses'] ?? 0),
        ])->contains(fn (float $value): bool => abs($value) > 0.0));
        $tableRows = $rows->map(function (array $row) use ($year): array {
            return [
                \Illuminate\Support\Carbon::createFromDate($year, $row['month_number'], 1)->locale('id')->translatedFormat('F Y'),
                format_rupiah($row['service_revenue'] ?? 0),
                format_rupiah($row['product_revenue'] ?? 0),
                format_rupiah($row['total_revenue'] ?? 0),
                format_rupiah($row['employee_commissions'] ?? 0),
                format_rupiah($row['expenses'] ?? 0),
                format_rupiah($row['net_profit'] ?? 0),
            ];
        })->all();

        $footer = [
            'Total',
            format_rupiah($rows->sum('service_revenue')),
            format_rupiah($rows->sum('product_revenue')),
            format_rupiah($rows->sum('total_revenue')),
            format_rupiah($rows->sum('employee_commissions')),
            format_rupiah($rows->sum('expenses')),
            format_rupiah($rows->sum('net_profit')),
        ];
    @endphp

    <div class="space-y-6">
        <x-report-filter :action="route('reports.monthly')" :showDateRange="false" :showYear="false" :filterKeys="['year']">
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
                :headers="[
                    'Bulan',
                    'Pendapatan layanan',
                    'Pendapatan produk',
                    'Total pendapatan',
                    'Total komisi pegawai',
                    'Pengeluaran',
                    'Laba bersih',
                ]"
                :rows="$tableRows"
                :footer="$footer"
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
