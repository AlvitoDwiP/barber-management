<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Detail Transaksi') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Informasi Transaksi</h3>
                            <p class="text-sm text-gray-600">Kode: {{ $transaction->transaction_code }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                href="{{ route('transactions.index') }}"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-100"
                            >
                                Kembali
                            </a>

                            <x-delete-form
                                :action="route('transactions.destroy', $transaction)"
                                button-text="Hapus"
                                confirm-message="Yakin ingin menghapus transaksi ini? Stok produk akan dikembalikan."
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="rounded-md border border-gray-200 p-4">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Tanggal Transaksi</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">
                                {{ $transaction->transaction_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                            </p>
                        </div>

                        <div class="rounded-md border border-gray-200 p-4">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Pegawai</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $transaction->employee?->name ?? '-' }}</p>
                        </div>

                        <div class="rounded-md border border-gray-200 p-4">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Metode Pembayaran</p>
                            <p class="mt-1 text-sm font-semibold uppercase text-gray-900">{{ $transaction->payment_method }}</p>
                        </div>

                        <div class="rounded-md border border-gray-200 p-4">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Total Transaksi</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">
                                Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Detail Item</h3>

                    @if ($transaction->transactionDetails->isEmpty())
                        <div class="rounded-md border border-gray-200 bg-gray-50 px-4 py-6 text-sm text-gray-600">
                            Belum ada detail item pada transaksi ini.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama Item</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Tipe</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Harga</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Qty</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Subtotal</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Komisi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($transaction->transactionDetails as $detail)
                                        <tr>
                                            <td class="px-4 py-3">{{ $detail->item_name ?: '-' }}</td>
                                            <td class="px-4 py-3 uppercase">{{ $detail->item_type }}</td>
                                            <td class="px-4 py-3">Rp {{ number_format((float) $detail->unit_price, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3">{{ number_format((int) $detail->qty, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3">Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3">Rp {{ number_format((float) $detail->commission_amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
