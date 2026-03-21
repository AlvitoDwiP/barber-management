@php
    $expenseCount = $expenses->count();
    $totalAmount = $expenses->sum(fn ($expense) => (float) $expense->amount);
    $latestExpenseDateLabel = $expenses->first()?->expense_date?->locale('id')->translatedFormat('d F Y') ?? '-';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Pengeluaran') }}</h2>
    </x-slot>

    <div class="space-y-5">
        <section class="admin-card p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#934C2D]">Pengeluaran operasional</p>
                        <span class="transaction-status-badge transaction-status-badge-draft">Expense</span>
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-slate-900 sm:text-2xl">Catat dan cek pengeluaran dengan cepat</h3>
                        <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                            Setiap expense di halaman ini akan masuk ke <span class="font-semibold text-slate-700">Pengeluaran Operasional</span>
                            dan ikut mengurangi <span class="font-semibold text-slate-700">Laba Operasional</span> di laporan.
                        </p>
                    </div>
                </div>

                <div class="flex w-full lg:w-auto lg:justify-end">
                    <a href="{{ route('expenses.create') }}" class="btn-brand-primary w-full justify-center lg:w-auto">
                        Catat Pengeluaran
                    </a>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Total tercatat</p>
                <p class="transaction-metric-value text-base">{{ $expenseCount }} pengeluaran</p>
            </article>

            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Nominal pengeluaran</p>
                <p class="transaction-metric-value text-base">{{ format_rupiah($totalAmount) }}</p>
            </article>

            <article class="transaction-metric-card">
                <p class="transaction-metric-label">Terakhir dicatat</p>
                <p class="transaction-metric-value text-base">{{ $latestExpenseDateLabel }}</p>
            </article>
        </section>

        <section class="admin-card p-4 sm:p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Daftar Pengeluaran</h3>
                    <p class="mt-1 text-sm text-slate-500">Nominal dibuat lebih menonjol supaya owner lebih cepat memastikan pengeluaran sudah tercatat dengan benar.</p>
                </div>

                <span class="inline-flex items-center rounded-full bg-[#FAF3EF] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[#7D4026]">
                    {{ $expenseCount }} pengeluaran
                </span>
            </div>

            @if ($expenses->isEmpty())
                <div class="transaction-empty-state">
                    <h4 class="text-lg font-semibold text-slate-900">Belum ada pengeluaran tercatat.</h4>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Mulai dari pengeluaran harian yang paling sering dicatat, seperti listrik, beli alat, atau beli produk stok.
                    </p>
                    <div class="mt-5 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('expenses.create') }}" class="btn-brand-primary">
                            Catat Pengeluaran
                        </a>
                    </div>
                </div>
            @else
                <div class="space-y-3 md:hidden">
                    @foreach ($expenses as $expense)
                        @php
                            $isLinkedToFreelance = $expense->freelancePayment !== null;
                            $categoryLabel = \Illuminate\Support\Str::of((string) $expense->category)->title()->value();
                        @endphp

                        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
                                        {{ $categoryLabel }}
                                    </span>
                                    <p class="mt-2 text-sm text-slate-500">{{ $expense->expense_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}</p>
                                </div>

                                <div class="text-right">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Nominal</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ format_rupiah($expense->amount) }}</p>
                                </div>
                            </div>

                            <div class="mt-3 rounded-xl bg-slate-50 px-3 py-3">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Catatan</p>
                                <p class="mt-1 text-sm leading-6 text-slate-700">{{ filled($expense->note) ? $expense->note : 'Tanpa catatan.' }}</p>
                            </div>

                            @if ($isLinkedToFreelance)
                                <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm text-emerald-900">
                                    Terhubung ke pembayaran freelance. Edit dan hapus dikelola dari flow payroll freelance.
                                </div>
                            @else
                                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                                    <a href="{{ route('expenses.edit', $expense) }}" class="btn-neutral-warm w-full justify-center sm:w-auto">
                                        Edit
                                    </a>
                                    <x-delete-form
                                        :action="route('expenses.destroy', $expense)"
                                        button-text="Hapus"
                                        confirm-message="Yakin ingin menghapus pengeluaran ini?"
                                        class="w-full justify-center sm:w-auto"
                                    />
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>

                <div class="admin-table-wrap hidden md:block">
                    <table class="admin-table w-full">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Catatan</th>
                                <th class="text-right">Nominal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($expenses as $expense)
                                @php
                                    $isLinkedToFreelance = $expense->freelancePayment !== null;
                                    $categoryLabel = \Illuminate\Support\Str::of((string) $expense->category)->title()->value();
                                @endphp

                                <tr class="hover:bg-slate-50/70">
                                    <td>{{ $expense->expense_date?->locale('id')->translatedFormat('d M Y') ?? '-' }}</td>
                                    <td>
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
                                            {{ $categoryLabel }}
                                        </span>
                                    </td>
                                    <td class="max-w-[380px] whitespace-normal text-slate-700">
                                        {{ filled($expense->note) ? $expense->note : 'Tanpa catatan.' }}
                                    </td>
                                    <td class="text-right font-semibold text-slate-900">{{ format_rupiah($expense->amount) }}</td>
                                    <td>
                                        @if ($isLinkedToFreelance)
                                            <div class="space-y-1">
                                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                    Pembayaran freelance
                                                </span>
                                                <p class="text-xs leading-5 text-slate-500">Edit dan hapus dikelola dari flow payroll freelance.</p>
                                            </div>
                                        @else
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('expenses.edit', $expense) }}" class="btn-neutral-warm">
                                                    Edit
                                                </a>
                                                <x-delete-form
                                                    :action="route('expenses.destroy', $expense)"
                                                    button-text="Hapus"
                                                    confirm-message="Yakin ingin menghapus pengeluaran ini?"
                                                />
                                            </div>
                                        @endif
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
