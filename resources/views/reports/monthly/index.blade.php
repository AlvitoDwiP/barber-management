<x-app-layout>
    <x-slot name="header">
        <x-report-page-header title="Laporan Bulanan" />
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $year = (int) ($year ?? now()->year);
        $yearOptions = collect(range(now()->year, now()->year - 4));
        $periodLabel = "Tahun {$year}";
        $tableRows = $rows->map(function (array $row) use ($year): array {
            return [
                \Illuminate\Support\Carbon::createFromDate($year, $row['month_number'], 1)->locale('id')->translatedFormat('F Y'),
                format_rupiah($row['service_revenue'] ?? 0),
                format_rupiah($row['product_revenue'] ?? 0),
                format_rupiah($row['expenses'] ?? 0),
                format_rupiah($row['barber_income'] ?? 0),
                format_rupiah($row['profit'] ?? 0),
            ];
        })->all();

        $footer = [
            'Total',
            format_rupiah($rows->sum('service_revenue')),
            format_rupiah($rows->sum('product_revenue')),
            format_rupiah($rows->sum('expenses')),
            format_rupiah($rows->sum('barber_income')),
            format_rupiah($rows->sum('profit')),
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

        <div class="admin-card">
            <p class="text-xs uppercase tracking-wide text-slate-500">Periode laporan</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $periodLabel }}</p>
        </div>

        <x-report-table
            :headers="['Bulan', 'Pendapatan Layanan', 'Pendapatan Produk', 'Pengeluaran', 'Total Pemasukan Barber', 'Keuntungan']"
            :rows="$tableRows"
            :footer="$footer"
            empty-message="Belum ada data laporan pada tahun ini."
        />
    </div>
</x-app-layout>
