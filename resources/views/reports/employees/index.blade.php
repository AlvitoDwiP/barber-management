<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Laporan Produktivitas Pegawai</h2>
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $month = (int) ($month ?? now()->month);
        $year = (int) ($year ?? now()->year);

        $monthOptions = collect(range(1, 12))->mapWithKeys(function (int $monthNumber) {
            return [
                $monthNumber => \Illuminate\Support\Carbon::createFromDate(null, $monthNumber, 1)->locale('id')->translatedFormat('F'),
            ];
        });
        $yearOptions = collect(range(now()->year, now()->year - 4));
        $periodLabel = \Illuminate\Support\Carbon::create($year, $month, 1)->locale('id')->translatedFormat('F Y');

        $tableRows = $rows->map(function ($row) {
            return [
                $row['employee_name'] ?? '-',
                number_format((int) ($row['total_services'] ?? 0), 0, ',', '.'),
                number_format((int) ($row['total_products'] ?? 0), 0, ',', '.'),
                format_rupiah($row['total_commission'] ?? 0),
            ];
        })->all();
    @endphp

    <div class="space-y-6">
        <x-report-filter :action="route('reports.employees')" :showDateRange="false" :showYear="false">
            <div>
                <label for="month" class="text-sm font-medium text-slate-700">Bulan</label>
                <select
                    id="month"
                    name="month"
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
                >
                    @foreach ($monthOptions as $optionMonth => $monthName)
                        <option value="{{ $optionMonth }}" @selected((int) $month === (int) $optionMonth)>{{ $monthName }}</option>
                    @endforeach
                </select>
                @error('month')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
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
            :headers="['Pegawai', 'Jumlah layanan', 'Jumlah produk', 'Total komisi']"
            :rows="$tableRows"
            empty-message="Belum ada aktivitas pegawai pada periode ini."
        />
    </div>
</x-app-layout>
