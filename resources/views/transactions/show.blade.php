@php
    $isLocked = $transaction->payrollPeriod?->status === \App\Models\PayrollPeriod::STATUS_CLOSED;
    $employeesInvolved = $transaction->transactionItems
        ->pluck('employee_name')
        ->filter()
        ->unique()
        ->values();
    $totalCommission = $transaction->transactionItems->sum(fn ($item) => (float) $item->commission_amount);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Detail Transaksi') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="admin-card">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#934C2D]">Dokumen Audit</p>
                        @include('transactions._partials.status-badge', ['transaction' => $transaction])
                    </div>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $transaction->transaction_code }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        {{ $transaction->transaction_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                    </p>
                    @if (filled($transaction->notes))
                        <p class="mt-3 max-w-3xl rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                            {{ $transaction->notes }}
                        </p>
                    @endif
                </div>

                <div class="flex w-full flex-col gap-3 lg:w-auto">
                    @unless ($isLocked)
                        <div class="flex flex-wrap items-center gap-2">
                            <x-delete-form
                                :action="route('transactions.destroy', $transaction)"
                                button-text="Hapus"
                                confirm-message="Yakin ingin menghapus transaksi ini? Stok produk akan dikembalikan."
                                variant="solid-danger"
                            />
                        </div>
                    @endunless

                    <a href="{{ route('transactions.index') }}" class="btn-brand-primary justify-center lg:self-end">
                        Selesai
                    </a>
                </div>
            </div>
        </section>

        @include('transactions._partials.lock-alert', ['transaction' => $transaction])

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-4">
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Tanggal Transaksi</p>
                <p class="transaction-metric-value">{{ $transaction->transaction_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}</p>
            </article>

            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Metode Pembayaran</p>
                <p class="transaction-metric-value">{{ strtoupper((string) $transaction->payment_method) }}</p>
            </article>

            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Pegawai Terlibat</p>
                <p class="transaction-metric-value">{{ $employeesInvolved->isNotEmpty() ? $employeesInvolved->implode(', ') : ($transaction->employee?->name ?? '-') }}</p>
            </article>

            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Total Final</p>
                <p class="transaction-metric-value">{{ format_rupiah($transaction->total_amount) }}</p>
            </article>
        </section>

        <section class="admin-card">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Item Snapshot Transaksi</h3>
                    <p class="mt-1 text-sm text-slate-500">Bagian ini ditata sebagai ringkasan audit final. Setelah transaksi tersimpan dari input harian, halaman ini menjadi titik acuan histori, bukan pintu edit utama.</p>
                </div>

                <span class="inline-flex items-center rounded-full bg-[#FAF3EF] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[#7D4026]">
                    {{ $transaction->transactionItems->count() }} item
                </span>
            </div>

            @if ($transaction->transactionItems->isEmpty())
                <div class="transaction-empty-state mt-5">
                    <h4 class="text-lg font-semibold text-slate-900">Belum ada item tersimpan.</h4>
                    <p class="mt-2 text-sm text-slate-500">Transaksi ini belum memiliki detail item yang bisa diaudit.</p>
                </div>
            @else
                <div class="mt-5 space-y-4 md:hidden">
                    @foreach ($transaction->transactionItems as $item)
                        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item->item_type === 'service' ? 'Layanan' : 'Produk' }}</p>
                                    <h4 class="mt-1 text-base font-semibold text-slate-900">{{ $item->item_name ?: '-' }}</h4>
                                </div>
                                <p class="text-sm font-semibold text-slate-900">{{ format_rupiah($item->subtotal) }}</p>
                            </div>

                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Pegawai</dt>
                                    <dd class="mt-1 text-slate-700">{{ $item->employee_name ?: '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Qty</dt>
                                    <dd class="mt-1 text-slate-700">{{ number_format((int) $item->qty, 0, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Harga snapshot</dt>
                                    <dd class="mt-1 text-slate-700">{{ format_rupiah($item->unit_price) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Komisi nominal</dt>
                                    <dd class="mt-1 text-slate-700">{{ format_rupiah($item->commission_amount) }}</dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Sumber komisi</dt>
                                    <dd class="mt-1 text-slate-700">
                                        {{ $item->commission_source === 'default' ? 'Global' : 'Override snapshot' }}
                                        ·
                                        {{ $item->commission_type === 'percent' ? rtrim(rtrim((string) $item->commission_value, '0'), '.').'%' : format_rupiah($item->commission_value) }}
                                    </dd>
                                </div>
                            </dl>
                        </article>
                    @endforeach
                </div>

                <div class="admin-table-wrap mt-5 hidden md:block">
                    <table class="admin-table w-full">
                        <thead>
                            <tr>
                                <th>Jenis Item</th>
                                <th>Nama Item Snapshot</th>
                                <th>Pegawai Snapshot</th>
                                <th>Qty</th>
                                <th class="text-right">Harga Snapshot</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">Komisi Nominal</th>
                                <th>Sumber Komisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($transaction->transactionItems as $item)
                                <tr class="hover:bg-slate-50/70">
                                    <td>{{ $item->item_type === 'service' ? 'Layanan' : 'Produk' }}</td>
                                    <td>{{ $item->item_name ?: '-' }}</td>
                                    <td>{{ $item->employee_name ?: '-' }}</td>
                                    <td>{{ number_format((int) $item->qty, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ format_rupiah($item->unit_price) }}</td>
                                    <td class="text-right font-semibold text-slate-900">{{ format_rupiah($item->subtotal) }}</td>
                                    <td class="text-right">{{ format_rupiah($item->commission_amount) }}</td>
                                    <td>
                                        <div class="space-y-1">
                                            <p class="font-medium text-slate-800">{{ $item->commission_source === 'default' ? 'Global' : 'Override snapshot' }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ $item->commission_type === 'percent'
                                                    ? rtrim(rtrim((string) $item->commission_value, '0'), '.').'%' 
                                                    : format_rupiah($item->commission_value) }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Subtotal Transaksi</p>
                <p class="transaction-metric-value">{{ format_rupiah($transaction->subtotal_amount) }}</p>
            </article>

            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Total Komisi Item</p>
                <p class="transaction-metric-value">{{ format_rupiah($totalCommission) }}</p>
            </article>

            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Total Final</p>
                <p class="transaction-metric-value">{{ format_rupiah($transaction->total_amount) }}</p>
            </article>
        </section>

        <section class="admin-card">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                @unless ($isLocked)
                    <div class="flex flex-wrap items-center gap-2">
                        <x-delete-form
                            :action="route('transactions.destroy', $transaction)"
                            button-text="Hapus"
                            confirm-message="Yakin ingin menghapus transaksi ini? Stok produk akan dikembalikan."
                            variant="solid-danger"
                        />
                    </div>
                @else
                    <div></div>
                @endunless

                <a href="{{ route('transactions.index') }}" class="btn-brand-primary self-start sm:self-auto">
                    Kembali
                </a>
            </div>
        </section>
    </div>
</x-app-layout>
