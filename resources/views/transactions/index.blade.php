@php
    $hasActiveFilters = filled($filters['start_date'] ?? null)
        || filled($filters['end_date'] ?? null)
        || filled($filters['employee_id'] ?? null)
        || filled($filters['payroll_status'] ?? null);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Daftar Transaksi') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="admin-card">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#934C2D]">Transaksi v1</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Daftar Transaksi</h3>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Pantau transaksi hasil input harian, buka detail audit, dan lihat dengan cepat mana transaksi yang masih bisa dihapus atau sudah terkunci karena payroll final.</p>
                </div>

                <a href="{{ route('transactions.daily-batch.create') }}" class="btn-brand-primary">
                    Input Harian
                </a>
            </div>
        </section>

        <section class="admin-card" x-data="{ filterOpen: @js($hasActiveFilters) }">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Filter Transaksi</h3>
                    <p class="mt-1 text-sm text-slate-500">Buka filter saat perlu menyaring transaksi berdasarkan rentang tanggal, pegawai, atau status payroll.</p>
                </div>

                <button
                    type="button"
                    class="btn-neutral-warm justify-center"
                    @click="filterOpen = !filterOpen"
                    :aria-expanded="filterOpen.toString()"
                    aria-controls="transaction-filter-form"
                >
                    <span x-text="filterOpen ? 'Tutup Filter' : 'Filter Transaksi'"></span>
                </button>
            </div>

            <div
                id="transaction-filter-form"
                x-cloak
                x-show="filterOpen"
                x-transition.opacity.duration.150ms
                class="mt-5 border-t border-slate-200 pt-5"
            >
                <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 gap-4 lg:grid-cols-5">
                    <div>
                        <x-input-label for="start_date" :value="__('Tanggal Dari')" />
                        <x-text-input
                            id="start_date"
                            name="start_date"
                            type="text"
                            class="mt-1 block w-full"
                            :value="$filters['start_date']"
                            data-flatpickr="date"
                            autocomplete="off"
                        />
                    </div>

                    <div>
                        <x-input-label for="end_date" :value="__('Tanggal Sampai')" />
                        <x-text-input
                            id="end_date"
                            name="end_date"
                            type="text"
                            class="mt-1 block w-full"
                            :value="$filters['end_date']"
                            data-flatpickr="date"
                            autocomplete="off"
                        />
                    </div>

                    <div>
                        <x-input-label for="employee_id" :value="__('Pegawai')" />
                        <select id="employee_id" name="employee_id" class="form-brand-control">
                            <option value="">Semua pegawai</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) ($filters['employee_id'] ?? '') === (string) $employee->id)>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="payroll_status" :value="__('Status Transaksi')" />
                        <select id="payroll_status" name="payroll_status" class="form-brand-control">
                            <option value="">Semua status</option>
                            <option value="unassigned" @selected(($filters['payroll_status'] ?? '') === 'unassigned')>Belum payroll</option>
                            <option value="open" @selected(($filters['payroll_status'] ?? '') === 'open')>Payroll open</option>
                            <option value="closed" @selected(($filters['payroll_status'] ?? '') === 'closed')>Payroll closed</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button class="w-full justify-center">
                            Terapkan Filter
                        </x-primary-button>
                        <a href="{{ route('transactions.index') }}" class="btn-neutral-warm w-full justify-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </section>

        <section class="admin-card">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Transaksi Tersimpan</h3>
                    <p class="mt-1 text-sm text-slate-500">Ringkasan berikut dibuat untuk cepat dipindai oleh admin operasional tanpa kehilangan konteks item dan status payroll.</p>
                </div>

                <span class="inline-flex items-center rounded-full bg-[#FAF3EF] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[#7D4026]">
                    {{ $transactions->total() }} transaksi
                </span>
            </div>

            @if ($transactions->isEmpty())
                <div class="transaction-empty-state">
                    <h4 class="text-lg font-semibold text-slate-900">Belum ada transaksi.</h4>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Semua transaksi sekarang dimasukkan lewat input harian. Gunakan halaman itu untuk menambahkan satu atau beberapa transaksi sekaligus pada hari kerja yang sama.</p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('transactions.daily-batch.create') }}" class="btn-brand-primary">
                            Input Harian
                        </a>
                    </div>
                </div>
            @else
                <div class="space-y-4 md:hidden">
                    @foreach ($transactions as $transaction)
                        @php
                            $isLocked = $transaction->payrollPeriod?->status === \App\Models\PayrollPeriod::STATUS_CLOSED;
                            $itemSummary = $transaction->transactionItems
                                ->map(fn ($item) => $item->item_name.((int) $item->qty > 1 ? ' x'.$item->qty : ''))
                                ->filter()
                                ->implode(', ');
                            $employeeSummary = $transaction->transactionItems
                                ->pluck('employee_name')
                                ->filter()
                                ->unique()
                                ->implode(', ');
                        @endphp

                        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $transaction->transaction_date?->locale('id')->translatedFormat('d F Y') }}</p>
                                    <h4 class="mt-1 text-base font-semibold text-slate-900">{{ $transaction->transaction_code }}</h4>
                                </div>

                                @include('transactions._partials.status-badge', ['transaction' => $transaction])
                            </div>

                            <dl class="mt-4 space-y-3 text-sm">
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Ringkasan item</dt>
                                    <dd class="mt-1 text-slate-700">{{ \Illuminate\Support\Str::limit($itemSummary ?: '-', 90) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wide text-slate-500">Pegawai terlibat</dt>
                                    <dd class="mt-1 text-slate-700">{{ $employeeSummary ?: ($transaction->employee?->name ?? '-') }}</dd>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-slate-500">Jumlah item</dt>
                                        <dd class="mt-1 font-medium text-slate-900">{{ (int) ($transaction->total_services ?? 0) + (int) ($transaction->total_products ?? 0) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-slate-500">Total</dt>
                                        <dd class="mt-1 font-semibold text-slate-900">{{ format_rupiah($transaction->total_amount) }}</dd>
                                    </div>
                                </div>
                            </dl>

                            @if ($isLocked)
                                <div class="mt-4 rounded-xl bg-amber-50 px-3 py-3 text-sm text-amber-900">
                                    Transaksi ini sudah terkunci payroll final sehingga tidak bisa dihapus.
                                </div>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('transactions.show', $transaction) }}" class="btn-brand-primary">
                                    Detail
                                </a>

                                @unless ($isLocked)
                                    <x-delete-form
                                        :action="route('transactions.destroy', $transaction)"
                                        button-text="Hapus"
                                        confirm-message="Yakin ingin menghapus transaksi ini? Stok produk akan dikembalikan."
                                        variant="solid-danger"
                                    />
                                @endunless
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="admin-table-wrap hidden md:block">
                    <table class="admin-table w-full">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kode</th>
                                <th>Ringkasan Item</th>
                                <th>Pegawai Terlibat</th>
                                <th class="text-right">Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($transactions as $transaction)
                                @php
                                    $isLocked = $transaction->payrollPeriod?->status === \App\Models\PayrollPeriod::STATUS_CLOSED;
                                    $itemSummary = $transaction->transactionItems
                                        ->map(fn ($item) => $item->item_name.((int) $item->qty > 1 ? ' x'.$item->qty : ''))
                                        ->filter()
                                        ->implode(', ');
                                    $employeeSummary = $transaction->transactionItems
                                        ->pluck('employee_name')
                                        ->filter()
                                        ->unique()
                                        ->implode(', ');
                                @endphp

                                <tr class="hover:bg-slate-50/70">
                                    <td>{{ $transaction->transaction_date?->locale('id')->translatedFormat('d M Y') }}</td>
                                    <td>
                                        <div class="space-y-1">
                                            <p class="font-semibold text-slate-900">{{ $transaction->transaction_code }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ (int) ($transaction->total_services ?? 0) }} layanan,
                                                {{ (int) ($transaction->total_products ?? 0) }} produk
                                            </p>
                                        </div>
                                    </td>
                                    <td class="max-w-[260px] whitespace-normal text-slate-700">
                                        {{ \Illuminate\Support\Str::limit($itemSummary ?: '-', 90) }}
                                    </td>
                                    <td class="max-w-[220px] whitespace-normal text-slate-700">
                                        {{ \Illuminate\Support\Str::limit($employeeSummary ?: ($transaction->employee?->name ?? '-'), 60) }}
                                    </td>
                                    <td class="text-right font-semibold text-slate-900">{{ format_rupiah($transaction->total_amount) }}</td>
                                    <td>
                                        <div class="space-y-2">
                                            @include('transactions._partials.status-badge', ['transaction' => $transaction])
                                            @if ($isLocked)
                                                <p class="text-xs leading-5 text-amber-800">Terkunci untuk edit dan hapus.</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('transactions.show', $transaction) }}" class="btn-brand-primary">
                                                Detail
                                            </a>

                                            @unless ($isLocked)
                                                <x-delete-form
                                                    :action="route('transactions.destroy', $transaction)"
                                                    button-text="Hapus"
                                                    confirm-message="Yakin ingin menghapus transaksi ini? Stok produk akan dikembalikan."
                                                    variant="solid-danger"
                                                />
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{ $transactions->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
