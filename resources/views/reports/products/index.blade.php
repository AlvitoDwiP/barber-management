<x-app-layout>
    <x-slot name="header">
        <x-report-page-header title="Laporan Penjualan Produk" />
    </x-slot>

    @php
        $rows = collect($rows ?? []);
        $table = $table ?? [
            'headers' => [],
            'displayRows' => [],
            'displayFooter' => [],
        ];
        $products = collect($products ?? []);
        $produkId = isset($produkId) ? (int) $produkId : null;
        $tanggalAwal = $tanggalAwal ?? now()->startOfMonth()->toDateString();
        $tanggalAkhir = $tanggalAkhir ?? now()->toDateString();
        $produkLabel = $produkId !== null
            ? ($products->firstWhere('id', $produkId)?->name ?? 'Produk terpilih')
            : 'Semua produk';
        $periodLabel = \Illuminate\Support\Carbon::parse($tanggalAwal)->locale('id')->translatedFormat('d M Y')
            .' - '.
            \Illuminate\Support\Carbon::parse($tanggalAkhir)->locale('id')->translatedFormat('d M Y');
        $exportQuery = array_filter([
            'tanggal_awal' => $tanggalAwal,
            'tanggal_akhir' => $tanggalAkhir,
            'produk_id' => $produkId,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="space-y-6">
        <x-report-filter
            :action="route('reports.products')"
            :showDateRange="true"
            :showYear="false"
            :startDateField="'tanggal_awal'"
            :endDateField="'tanggal_akhir'"
            :startDate="$tanggalAwal"
            :endDate="$tanggalAkhir"
            :filterKeys="['tanggal_awal', 'tanggal_akhir', 'produk_id']"
        >
            <x-slot name="actions">
                <a href="{{ route('reports.products.export.csv', $exportQuery) }}" class="btn-neutral-warm shrink-0">
                    Export CSV
                </a>
            </x-slot>

            <div>
                <label for="produk_id" class="text-sm font-medium text-slate-700">Produk</label>
                <select
                    id="produk_id"
                    name="produk_id"
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
                >
                    <option value="">Semua produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected($produkId === (int) $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
                @error('produk_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </x-report-filter>

        <p class="px-1 text-xs uppercase tracking-wide text-slate-500">
            Periode {{ $periodLabel }} · {{ $produkLabel }}
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
                    <h3 class="text-base font-semibold text-slate-900">Belum ada data penjualan produk</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Tidak ada penjualan produk yang tercatat untuk {{ $periodLabel }}.
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        Ubah filter tanggal atau pilih produk lain untuk melihat penjualannya.
                    </p>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
