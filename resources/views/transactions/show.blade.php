@php
    $isLocked = $transaction->payrollPeriod?->status === \App\Models\PayrollPeriod::STATUS_CLOSED;
    $employeesInvolved = $transaction->transactionItems
        ->pluck('employee_name')
        ->filter()
        ->unique()
        ->values();
    $totalCommission = $transaction->transactionItems->sum(fn ($item) => (float) $item->commission_amount);
    $transactionDateLabel = $transaction->transaction_date?->locale('id')->translatedFormat('d F Y') ?? '-';
    $paymentMethodLabel = match ((string) $transaction->payment_method) {
        'cash' => 'Cash',
        'qr' => 'QRIS',
        default => \Illuminate\Support\Str::of((string) $transaction->payment_method)
            ->replace('_', ' ')
            ->title()
            ->value(),
    };
    $itemCount = $transaction->transactionItems->count();
    $serviceCount = (int) $transaction->transactionItems
        ->where('item_type', 'service')
        ->sum(fn ($item) => (int) $item->qty);
    $productCount = (int) $transaction->transactionItems
        ->where('item_type', 'product')
        ->sum(fn ($item) => (int) $item->qty);
    $itemSummaryParts = collect([
        $serviceCount > 0 ? $serviceCount.' layanan' : null,
        $productCount > 0 ? $productCount.' produk' : null,
    ])->filter()->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Detail Transaksi') }}</h2>
    </x-slot>

    <div class="space-y-5">
        <section class="admin-card p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#934C2D]">Detail transaksi</p>
                        @include('transactions._partials.status-badge', ['transaction' => $transaction])
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-slate-900 sm:text-2xl">{{ $transaction->transaction_code }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $transactionDateLabel }}</p>
                    </div>
                </div>

                <div class="flex w-full lg:w-auto lg:justify-end">
                    <a href="{{ route('transactions.index') }}" class="btn-neutral-warm w-full justify-center lg:w-auto">
                        Kembali ke Daftar
                    </a>
                </div>
            </div>
        </section>

        @include('transactions._partials.lock-alert', ['transaction' => $transaction])

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(280px,0.85fr)]">
            <article class="admin-card p-4 sm:p-5">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Ringkasan Utama</h3>
                    <p class="mt-1 text-sm text-slate-500">Info inti dibuat singkat supaya lebih cepat dicek di mobile.</p>
                </div>

                <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="transaction-metric-label">Tanggal transaksi</dt>
                        <dd class="transaction-metric-value mt-1">{{ $transactionDateLabel }}</dd>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="transaction-metric-label">Pegawai / barber</dt>
                        <dd class="transaction-metric-value mt-1">{{ $employeesInvolved->isNotEmpty() ? $employeesInvolved->implode(', ') : ($transaction->employee?->name ?? '-') }}</dd>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="transaction-metric-label">Metode pembayaran</dt>
                        <dd class="transaction-metric-value mt-1">{{ $paymentMethodLabel }}</dd>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="transaction-metric-label">Isi transaksi</dt>
                        <dd class="transaction-metric-value mt-1">{{ $itemSummaryParts->isNotEmpty() ? $itemSummaryParts->implode(' · ') : ($itemCount.' item') }}</dd>
                    </div>
                </dl>

                @if (filled($transaction->notes))
                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="transaction-metric-label">Catatan</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $transaction->notes }}</p>
                    </div>
                @endif
            </article>

            <article class="rounded-2xl border border-[#E6D7CF] bg-[#FCF8F5] px-4 py-4 shadow-sm sm:px-5 sm:py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#7D4026]">Total transaksi</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ format_rupiah($transaction->total_amount) }}</p>

                <dl class="mt-5 space-y-3">
                    <div class="flex items-start justify-between gap-3 text-sm">
                        <dt class="text-slate-500">Subtotal</dt>
                        <dd class="font-medium text-slate-900">{{ format_rupiah($transaction->subtotal_amount) }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 text-sm">
                        <dt class="text-slate-500">Total komisi item</dt>
                        <dd class="font-medium text-slate-900">{{ format_rupiah($totalCommission) }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 border-t border-[#E6D7CF] pt-3 text-sm">
                        <dt class="font-semibold text-slate-700">Total final</dt>
                        <dd class="text-base font-semibold text-slate-900">{{ format_rupiah($transaction->total_amount) }}</dd>
                    </div>
                </dl>
            </article>
        </section>

        <section class="admin-card p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Item Transaksi</h3>
                    <p class="mt-1 text-sm text-slate-500">Semua layanan dan produk yang masuk ke transaksi ini ditampilkan di bawah.</p>
                </div>

                <span class="inline-flex items-center rounded-full bg-[#FAF3EF] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[#7D4026]">
                    {{ $itemCount }} item
                </span>
            </div>

            @if ($transaction->transactionItems->isEmpty())
                <div class="transaction-empty-state mt-4">
                    <h4 class="text-lg font-semibold text-slate-900">Belum ada rincian item.</h4>
                    <p class="mt-2 text-sm text-slate-500">Detail item untuk transaksi ini belum tersedia, jadi belum ada yang bisa dicek di halaman ini.</p>
                </div>
            @else
                <div class="mt-4 space-y-3 md:hidden">
                    @foreach ($transaction->transactionItems as $item)
                        @php
                            $itemTypeLabel = $item->item_type === 'service' ? 'Layanan' : 'Produk';
                            $itemTypeBadgeClasses = $item->item_type === 'service'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                : 'border-sky-200 bg-sky-50 text-sky-700';
                            $commissionRuleLabel = $item->commission_source === 'default' ? 'Global' : 'Khusus transaksi';
                            $commissionValueLabel = $item->commission_type === 'percent'
                                ? rtrim(rtrim((string) $item->commission_value, '0'), '.').'%'
                                : format_rupiah($item->commission_value);
                        @endphp

                        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $itemTypeBadgeClasses }}">
                                        {{ $itemTypeLabel }}
                                    </span>
                                    <h4 class="mt-2 text-base font-semibold text-slate-900">{{ $item->item_name ?: '-' }}</h4>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Subtotal</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ format_rupiah($item->subtotal) }}</p>
                                </div>
                            </div>

                            <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Pegawai</dt>
                                    <dd class="mt-1 text-slate-700">{{ $item->employee_name ?: '-' }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Qty</dt>
                                    <dd class="mt-1 text-slate-700">{{ number_format((int) $item->qty, 0, ',', '.') }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Harga</dt>
                                    <dd class="mt-1 text-slate-700">{{ format_rupiah($item->unit_price) }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5">
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Komisi</dt>
                                    <dd class="mt-1 text-slate-700">{{ format_rupiah($item->commission_amount) }}</dd>
                                </div>
                                <div class="col-span-2 rounded-xl border border-[#E6D7CF] bg-[#FCF8F5] px-3 py-2.5">
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Aturan komisi</dt>
                                    <dd class="mt-1 text-slate-700">{{ $commissionRuleLabel }} · {{ $commissionValueLabel }}</dd>
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
                                <th>Nama Item</th>
                                <th>Pegawai</th>
                                <th>Qty</th>
                                <th class="text-right">Harga Saat Transaksi</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-right">Komisi</th>
                                <th>Sumber Komisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($transaction->transactionItems as $item)
                                <tr class="hover:bg-slate-50/70">
                                    <td>
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $item->item_type === 'service' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-sky-200 bg-sky-50 text-sky-700' }}">
                                            {{ $item->item_type === 'service' ? 'Layanan' : 'Produk' }}
                                        </span>
                                    </td>
                                    <td>{{ $item->item_name ?: '-' }}</td>
                                    <td>{{ $item->employee_name ?: '-' }}</td>
                                    <td>{{ number_format((int) $item->qty, 0, ',', '.') }}</td>
                                    <td class="text-right">{{ format_rupiah($item->unit_price) }}</td>
                                    <td class="text-right font-semibold text-slate-900">{{ format_rupiah($item->subtotal) }}</td>
                                    <td class="text-right">{{ format_rupiah($item->commission_amount) }}</td>
                                    <td>
                                        <div class="space-y-1">
                                            <p class="font-medium text-slate-800">{{ $item->commission_source === 'default' ? 'Global' : 'Khusus transaksi' }}</p>
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

        <section class="admin-card p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">{{ $isLocked ? 'Transaksi sudah final' : 'Aksi lanjutan' }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $isLocked
                            ? 'Detail masih bisa dicek, tetapi edit dan hapus sudah dinonaktifkan karena transaksi ini masuk payroll final.'
                            : 'Buka edit jika ada yang perlu dikoreksi. Jika sudah selesai mengecek, kembali ke daftar transaksi.' }}
                    </p>
                </div>

                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap">
                    @unless ($isLocked)
                        <a href="{{ route('transactions.edit', $transaction) }}" class="btn-brand-primary w-full justify-center sm:w-auto">
                            Edit Transaksi
                        </a>
                    @endunless

                    <a href="{{ route('transactions.index') }}" class="btn-neutral-warm w-full justify-center sm:w-auto">
                        Selesai
                    </a>

                    @unless ($isLocked)
                        <x-delete-form
                            :action="route('transactions.destroy', $transaction)"
                            button-text="Hapus"
                            confirm-message="Yakin ingin menghapus transaksi ini? Stok produk akan dikembalikan."
                            variant="solid-danger"
                            class="w-full justify-center sm:w-auto"
                        />
                    @endunless
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
