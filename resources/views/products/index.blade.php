<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Produk') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="admin-card">
            @include('partials.crud.index-toolbar', [
                'title' => 'Daftar Produk',
                'description' => 'Cek harga dan stok produk yang dipakai di transaksi supaya owner bisa review cepat tanpa membuka form edit.',
                'count' => $products->count(),
                'createUrl' => route('products.create'),
                'createLabel' => 'Tambah Produk',
            ])

            @if ($products->isEmpty())
                @include('partials.crud.empty-state', [
                    'title' => 'Belum ada produk',
                    'message' => 'Tambahkan produk yang dijual atau dipakai di barbershop agar stok dan transaksi bisa tercatat dengan rapi.',
                    'actionUrl' => route('products.create'),
                    'actionLabel' => 'Tambah Produk',
                ])
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table w-full min-w-[760px]">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($products as $product)
                                <tr class="hover:bg-slate-50/70">
                                    <td>
                                        <div class="font-medium text-slate-900">{{ $product->name }}</div>
                                    </td>
                                    <td class="font-semibold text-slate-900">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</td>
                                    <td>{{ number_format((int) $product->stock, 0, ',', '.') }}</td>
                                    <td>
                                        @include('partials.crud.action-buttons', [
                                            'editUrl' => route('products.edit', $product),
                                            'deleteUrl' => route('products.destroy', $product),
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
