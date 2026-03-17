<x-app-layout>
    <x-slot name="header">
        <x-report-page-header title="Laporan Kinerja Pegawai" />
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $table = $table ?? [
            'headers' => [],
            'displayRows' => [],
            'displayFooter' => [],
        ];
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
        $exportQuery = array_filter([
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'pegawai_id' => $pegawaiId,
        ], fn ($value) => $value !== null && $value !== '');
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
            <x-slot name="actions">
                <a href="{{ route('reports.employees.export.csv', $exportQuery) }}" class="btn-neutral-warm shrink-0">
                    Export CSV
                </a>
            </x-slot>

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
                :headers="$table['headers']"
                :rows="$table['displayRows']"
                :footer="$table['displayFooter']"
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
