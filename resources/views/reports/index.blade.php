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
                'title' => 'Laporan Metode Pembayaran',
                'description' => 'Pantau arus pembayaran transaksi per bulan. Cocok untuk membaca kas masuk, bukan laba operasional.',
                'url' => route('reports.payment'),
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
            <p class="text-sm text-slate-600">Pilih laporan utama untuk melihat performa bisnis, arus pembayaran, dan kontribusi operasional dengan istilah yang konsisten.</p>
        </div>

        <div class="mt-5 space-y-3">
            @foreach ($reportMenus as $menu)
                <a
                    href="{{ $menu['url'] }}"
                    class="flex w-full flex-col gap-2 rounded-xl border border-[var(--warm-200)] bg-white px-5 py-4 text-left shadow-sm transition hover:border-[#D1AB99] hover:bg-[var(--cream-50)] sm:px-6 sm:py-5"
                >
                    <p class="text-base font-semibold leading-6 text-slate-900">{{ $menu['title'] }}</p>
                    <p class="text-sm leading-6 text-slate-500">{{ $menu['description'] }}</p>
                </a>
            @endforeach
        </div>
    </section>
</x-app-layout>
