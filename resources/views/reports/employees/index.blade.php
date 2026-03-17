<x-app-layout>
    <x-slot name="header">
        <x-report-page-header title="Laporan Kinerja Pegawai" />
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $employees = collect($employees ?? []);
        $pegawaiId = isset($pegawaiId) ? (int) $pegawaiId : null;
        $tanggalAwal = $tanggalAwal ?? now()->startOfMonth()->toDateString();
        $tanggalAkhir = $tanggalAkhir ?? now()->toDateString();
        $pegawaiLabel = $pegawaiId !== null
            ? ($employees->firstWhere('id', $pegawaiId)?->name ?? 'Pegawai terpilih')
            : 'Semua pegawai';
        $periodLabel = \Illuminate\Support\Carbon::parse($tanggalAwal)->locale('id')->translatedFormat('d M Y')
            .' - '.
            \Illuminate\Support\Carbon::parse($tanggalAkhir)->locale('id')->translatedFormat('d M Y');

        $tableRows = $rows->map(function (array $row): array {
            return [
                $row['employee_name'] ?? '-',
                number_format((int) ($row['total_transactions'] ?? 0), 0, ',', '.'),
                number_format((int) ($row['total_services'] ?? 0), 0, ',', '.'),
                format_rupiah($row['service_revenue'] ?? 0),
                number_format((int) ($row['total_products'] ?? 0), 0, ',', '.'),
                format_rupiah($row['product_revenue'] ?? 0),
                format_rupiah($row['total_commission'] ?? 0),
            ];
        })->all();

        $footer = [
            'Total',
            number_format((int) $rows->sum('total_transactions'), 0, ',', '.'),
            number_format((int) $rows->sum('total_services'), 0, ',', '.'),
            format_rupiah($rows->sum('service_revenue')),
            number_format((int) $rows->sum('total_products'), 0, ',', '.'),
            format_rupiah($rows->sum('product_revenue')),
            format_rupiah($rows->sum('total_commission')),
        ];
    @endphp

    <div class="space-y-6">
        <x-report-filter
            :action="route('reports.employees')"
            :showDateRange="true"
            :showYear="false"
            :startDateField="'tanggal_awal'"
            :endDateField="'tanggal_akhir'"
            :startDate="$tanggalAwal"
            :endDate="$tanggalAkhir"
            :filterKeys="['tanggal_awal', 'tanggal_akhir', 'pegawai_id']"
        >
            <div>
                <label for="pegawai_id" class="text-sm font-medium text-slate-700">Pegawai</label>
                <select
                    id="pegawai_id"
                    name="pegawai_id"
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
                >
                    <option value="">Semua pegawai</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected($pegawaiId === (int) $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
                @error('pegawai_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </x-report-filter>

        <p class="px-1 text-xs uppercase tracking-wide text-slate-500">
            Periode {{ $periodLabel }} · {{ $pegawaiLabel }}
        </p>

        @if ($rows->isNotEmpty())
            <x-report-table
                :headers="[
                    'Nama pegawai',
                    'Jumlah transaksi',
                    'Jumlah layanan dikerjakan',
                    'Omzet layanan',
                    'Jumlah produk terjual',
                    'Omzet produk',
                    'Total komisi',
                ]"
                :rows="$tableRows"
                :footer="$footer"
            />
        @else
            <section class="admin-card">
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                    <h3 class="text-base font-semibold text-slate-900">Belum ada data kinerja pegawai</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Tidak ada transaksi pegawai yang tercatat untuk {{ $periodLabel }}.
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        Ubah filter tanggal atau pilih pegawai lain untuk melihat kontribusinya.
                    </p>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
