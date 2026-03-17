<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Laporan</h2>
    </x-slot>

    <div class="admin-card">
        <h3 class="text-base font-semibold text-slate-900">Daftar laporan</h3>
        <p class="mt-1 text-sm text-slate-600">Pilih jenis laporan yang ingin dibuka.</p>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <a href="{{ route('reports.daily') }}" class="btn-brand-primary w-full text-center">
                Laporan harian
            </a>
            <a href="{{ route('reports.monthly') }}" class="btn-brand-primary w-full text-center">
                Laporan bulanan
            </a>
            <a href="{{ route('reports.payment') }}" class="btn-brand-primary w-full text-center">
                Laporan metode pembayaran
            </a>
            <a href="{{ route('reports.products') }}" class="btn-brand-primary w-full text-center">
                Laporan penjualan produk
            </a>
            <a href="{{ route('reports.employees') }}" class="btn-brand-primary w-full text-center sm:col-span-2">
                Laporan kinerja pegawai
            </a>
        </div>
    </div>
</x-app-layout>
