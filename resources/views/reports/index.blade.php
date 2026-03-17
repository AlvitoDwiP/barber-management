<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Laporan</h2>
    </x-slot>

    @php
        $reportMenus = [
            [
                'title' => 'Laporan Harian',
                'description' => 'Rekap harian transaksi, pembayaran, dan pengeluaran per tanggal.',
                'url' => route('reports.daily'),
            ],
            [
                'title' => 'Laporan Bulanan',
                'description' => 'Ringkasan performa bisnis per bulan untuk satu tahun terpilih.',
                'url' => route('reports.monthly'),
            ],
            [
                'title' => 'Laporan Kinerja Pegawai',
                'description' => 'Lihat kontribusi tiap pegawai berdasarkan transaksi, omzet, dan komisi.',
                'url' => route('reports.employees'),
            ],
            [
                'title' => 'Laporan Penjualan Produk',
                'description' => 'Pantau penjualan produk berdasarkan qty, harga rata-rata, dan omzet.',
                'url' => route('reports.products'),
            ],
        ];
    @endphp

    <section class="admin-card">
        <div class="flex flex-col gap-2">
            <h3 class="text-base font-semibold text-slate-900">Menu laporan</h3>
            <p class="text-sm text-slate-600">Pilih laporan utama yang ingin dibuka untuk melihat performa bisnis hair studio.</p>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($reportMenus as $menu)
                <a
                    href="{{ $menu['url'] }}"
                    class="rounded-xl border border-[var(--warm-200)] bg-white p-5 text-left shadow-sm transition hover:border-[#D1AB99] hover:bg-[var(--cream-50)]"
                >
                    <p class="text-sm font-semibold text-slate-900">{{ $menu['title'] }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $menu['description'] }}</p>
                </a>
            @endforeach
        </div>
    </section>
</x-app-layout>
