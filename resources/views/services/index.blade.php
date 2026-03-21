<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Layanan') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="admin-card">
            @include('partials.crud.index-toolbar', [
                'title' => 'Daftar Layanan',
                'description' => 'Rapikan layanan dan tarif yang muncul di transaksi agar owner bisa cek harga dengan cepat dari satu tempat.',
                'count' => $services->count(),
                'createUrl' => route('services.create'),
                'createLabel' => 'Tambah Layanan',
            ])

            @if ($services->isEmpty())
                @include('partials.crud.empty-state', [
                    'title' => 'Belum ada layanan',
                    'message' => 'Tambahkan layanan utama barbershop agar transaksi bisa dicatat lebih cepat dan laporan tetap konsisten.',
                    'actionUrl' => route('services.create'),
                    'actionLabel' => 'Tambah Layanan',
                ])
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table w-full min-w-[720px]">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($services as $service)
                                <tr class="hover:bg-slate-50/70">
                                    <td>
                                        <div class="font-medium text-slate-900">{{ $service->name }}</div>
                                    </td>
                                    <td class="font-semibold text-slate-900">Rp {{ number_format((float) $service->price, 0, ',', '.') }}</td>
                                    <td>
                                        @include('partials.crud.action-buttons', [
                                            'editUrl' => route('services.edit', $service),
                                            'deleteUrl' => route('services.destroy', $service),
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
