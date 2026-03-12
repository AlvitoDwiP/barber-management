<x-app-layout>
    <x-slot name="header">
        <x-report-page-header title="Laporan Metode Pembayaran" />
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $year = (int) ($year ?? now()->year);
        $yearOptions = collect(range(now()->year, now()->year - 4));

        $tableRows = $rows->map(function ($row) {
            return [
                \Illuminate\Support\Carbon::createFromDate(null, (int) ($row['month_number'] ?? 1), 1)->locale('id')->translatedFormat('F'),
                format_rupiah($row['total_cash'] ?? 0),
                format_rupiah($row['total_qr'] ?? 0),
                number_format((int) ($row['total_transactions'] ?? 0), 0, ',', '.'),
            ];
        })->all();

        $footer = [
            'Total',
            format_rupiah($rows->sum('total_cash')),
            format_rupiah($rows->sum('total_qr')),
            number_format((int) $rows->sum('total_transactions'), 0, ',', '.'),
        ];
    @endphp

    <div class="space-y-6">
        <x-report-filter :action="route('reports.payment')" :showDateRange="false" :showYear="false" :filterKeys="['year']">
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
            <p class="mt-1 text-sm font-semibold text-slate-900">Tahun {{ $year }}</p>
        </div>

        <x-report-table
            :headers="['Bulan', 'Total cash', 'Total qr', 'Total transaksi']"
            :rows="$tableRows"
            :footer="$footer"
            empty-message="Belum ada data transaksi pada tahun ini."
        />
    </div>
</x-app-layout>
