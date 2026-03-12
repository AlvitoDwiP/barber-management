<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Detail Transaksi') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="admin-card">
            <div class="mb-5 space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kode Transaksi</p>
                    <h3 class="mt-1 text-xl font-bold text-slate-900">{{ $transaction->transaction_code }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Informasi ringkas transaksi dan item snapshot.</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            href="{{ route('transactions.edit', $transaction) }}"
                            class="btn-brand-soft shrink-0"
                        >
                            Edit
                        </a>

                        <x-delete-form
                            :action="route('transactions.destroy', $transaction)"
                            button-text="Hapus"
                            confirm-message="Yakin ingin menghapus transaksi ini? Stok produk akan dikembalikan."
                            class="shrink-0"
                        />
                    </div>

                    <a
                        href="{{ route('transactions.index') }}"
                        class="btn-brand-primary shrink-0 self-start sm:self-auto"
                    >
                        Selesai
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Tanggal Transaksi</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $transaction->transaction_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Pegawai</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $transaction->employee?->name ?? '-' }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Metode Pembayaran</p>
                    <p class="mt-1">
                        <span class="payment-badge {{ $transaction->payment_method === 'cash' ? 'payment-badge-cash' : 'payment-badge-qr' }}">
                            {{ $transaction->payment_method }}
                        </span>
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total Transaksi</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}</p>
                </div>
            </div>
        </section>

        <section class="admin-card">
            <h3 class="mb-4 text-base font-semibold text-slate-900">Detail Item</h3>

            @if ($transaction->transactionItems->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-600">
                    Belum ada detail item pada transaksi ini.
                </div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table w-full">
                        <thead>
                            <tr>
                                <th>Nama Item</th>
                                <th>Tipe</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                                <th>Komisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($transaction->transactionItems as $detail)
                                <tr class="hover:bg-slate-50/70">
                                    <td>{{ $detail->item_name ?: '-' }}</td>
                                    <td class="uppercase">{{ $detail->item_type }}</td>
                                    <td>Rp {{ number_format((float) $detail->unit_price, 0, ',', '.') }}</td>
                                    <td>{{ number_format((int) $detail->qty, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format((float) $detail->commission_amount, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
