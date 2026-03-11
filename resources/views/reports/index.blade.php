<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Laporan</h2>
    </x-slot>

    <div class="admin-card">
        <h3 class="text-base font-semibold text-slate-900">Daftar laporan</h3>
        <p class="mt-1 text-sm text-slate-600">Pilih jenis laporan yang ingin dibuka.</p>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <a href="{{ route('reports.daily') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                Laporan harian
            </a>
            <a href="{{ route('reports.monthly') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                Laporan bulanan
            </a>
            <a href="{{ route('reports.payment') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                Laporan metode pembayaran
            </a>
            <a href="{{ route('reports.products') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                Laporan produk
            </a>
            <a href="{{ route('reports.employees') }}" class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 sm:col-span-2">
                Laporan produktivitas pegawai
            </a>
        </div>
    </div>
</x-app-layout>

