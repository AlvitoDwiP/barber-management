<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Laporan Bulanan</h2>
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $year = (int) ($year ?? now()->year);
        $yearOptions = collect(range(now()->year, now()->year - 4));
        $rowsByMonth = $rows->keyBy('month_number');

        $tableRows = collect(range(1, 12))->map(function (int $month) use ($rowsByMonth) {
            $row = $rowsByMonth->get($month, [
                'month_number' => $month,
                'service_revenue' => 0,
                'product_revenue' => 0,
                'total_revenue' => 0,
            ]);

            return [
                \Illuminate\Support\Carbon::createFromDate(null, $month, 1)->locale('id')->translatedFormat('F'),
                'Rp '.number_format((float) ($row['service_revenue'] ?? 0), 0, ',', '.'),
                'Rp '.number_format((float) ($row['product_revenue'] ?? 0), 0, ',', '.'),
                'Rp '.number_format((float) ($row['total_revenue'] ?? 0), 0, ',', '.'),
            ];
        })->all();

        $footer = [
            'Total',
            'Rp '.number_format((float) $rows->sum('service_revenue'), 0, ',', '.'),
            'Rp '.number_format((float) $rows->sum('product_revenue'), 0, ',', '.'),
            'Rp '.number_format((float) $rows->sum('total_revenue'), 0, ',', '.'),
        ];
    @endphp

    <div class="space-y-6">
        <x-report-filter :action="route('reports.monthly')" :showDateRange="false" :showYear="false">
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

        <x-report-table
            :headers="['Bulan', 'Pendapatan layanan', 'Pendapatan produk', 'Total pendapatan']"
            :rows="$tableRows"
            :footer="$footer"
            empty-message="Belum ada data transaksi pada tahun ini."
        />
    </div>
</x-app-layout>
