<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Transaksi') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="admin-card">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Filter Transaksi</h3>
                    <p class="text-sm text-slate-500">Saring data berdasarkan tanggal, pegawai, dan metode pembayaran.</p>
                </div>

                <a
                    href="{{ route('transactions.create') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-[#934C2D] bg-[#934C2D] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:border-[#7D4026] hover:bg-[#7D4026] focus:outline-none focus:ring-2 focus:ring-[#A85F3B] focus:ring-offset-2"
                >
                    Tambah Transaksi
                </a>
            </div>

            <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div>
                    <x-input-label for="start_date" :value="__('Tanggal Mulai')" />
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
                    <x-input-label for="end_date" :value="__('Tanggal Akhir')" />
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
                            <option value="{{ $employee->id }}" @selected((string) $filters['employee_id'] === (string) $employee->id)>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="payment_method" :value="__('Metode Pembayaran')" />
                    <select id="payment_method" name="payment_method" class="form-brand-control">
                        <option value="">Semua metode</option>
                        <option value="cash" @selected($filters['payment_method'] === 'cash')>cash</option>
                        <option value="qr" @selected($filters['payment_method'] === 'qr')>qr</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <x-primary-button class="w-full">Filter</x-primary-button>
                    <a
                        href="{{ route('transactions.index') }}"
                        class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 transition hover:bg-slate-100"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="admin-card">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-slate-900">Daftar Transaksi</h3>
                <span class="rounded-full bg-[#FAF3EF] px-3 py-1 text-xs font-medium text-[#7D4026]">{{ $transactions->total() }} data</span>
            </div>

            @if ($transactions->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-600">
                    Belum ada data transaksi.
                </div>
            @else
                <div class="space-y-3 md:hidden">
                    @foreach ($transactions as $transaction)
                        <article class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">Tanggal</p>
                                    <p class="text-sm font-semibold text-slate-900">{{ $transaction->transaction_date?->locale('id')->translatedFormat('d F Y') }}</p>
                                </div>
                                <span class="payment-badge {{ $transaction->payment_method === 'cash' ? 'payment-badge-cash' : 'payment-badge-qr' }}">{{ $transaction->payment_method }}</span>
                            </div>

                            <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <dt class="text-xs text-slate-500">Pegawai</dt>
                                    <dd class="font-medium text-slate-800">{{ $transaction->employee?->name ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Layanan</dt>
                                    <dd class="font-medium text-slate-800">{{ $transaction->total_services }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Produk</dt>
                                    <dd class="font-medium text-slate-800">{{ $transaction->total_products }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Total</dt>
                                    <dd class="font-semibold text-slate-900">Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}</dd>
                                </div>
                            </dl>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <a
                                    href="{{ route('transactions.show', $transaction->id) }}"
                                    class="inline-flex items-center rounded-lg border border-[#E5CBC0] bg-[#FAF3EF] px-3 py-2 text-xs font-semibold uppercase tracking-widest text-[#7D4026] transition hover:border-[#D9B4A2] hover:bg-[#F3E5DD]"
                                >
                                    Detail
                                </a>

                                <a
                                    href="{{ route('transactions.edit', $transaction->id) }}"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 transition hover:bg-slate-100"
                                >
                                    Edit
                                </a>

                                <x-delete-form
                                    :action="route('transactions.destroy', $transaction->id)"
                                    button-text="Hapus"
                                    confirm-message="Yakin ingin menghapus transaksi ini? Stok produk akan dikembalikan."
                                />
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="admin-table-wrap hidden md:block">
                    <table class="admin-table w-full">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Pegawai</th>
                                <th>Layanan</th>
                                <th>Produk</th>
                                <th>Metode Bayar</th>
                                <th>Total</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($transactions as $transaction)
                                <tr class="hover:bg-slate-50/70">
                                    <td>{{ $transaction->transaction_date?->locale('id')->translatedFormat('d F Y') }}</td>
                                    <td>{{ $transaction->employee?->name ?? '-' }}</td>
                                    <td>{{ $transaction->total_services }}</td>
                                    <td>{{ $transaction->total_products }}</td>
                                    <td>
                                        <span class="payment-badge {{ $transaction->payment_method === 'cash' ? 'payment-badge-cash' : 'payment-badge-qr' }}">
                                            {{ $transaction->payment_method }}
                                        </span>
                                    </td>
                                    <td class="font-semibold text-slate-900">Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <a
                                                href="{{ route('transactions.show', $transaction->id) }}"
                                                class="inline-flex items-center rounded-lg border border-[#E5CBC0] bg-[#FAF3EF] px-3 py-2 text-xs font-semibold uppercase tracking-widest text-[#7D4026] transition hover:border-[#D9B4A2] hover:bg-[#F3E5DD]"
                                            >
                                                Detail
                                            </a>

                                            <a
                                                href="{{ route('transactions.edit', $transaction->id) }}"
                                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 transition hover:bg-slate-100"
                                            >
                                                Edit
                                            </a>

                                            <x-delete-form
                                                :action="route('transactions.destroy', $transaction->id)"
                                                button-text="Hapus"
                                                confirm-message="Yakin ingin menghapus transaksi ini? Stok produk akan dikembalikan."
                                            />
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
