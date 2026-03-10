<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Transaksi') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
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
                            <select
                                id="employee_id"
                                name="employee_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
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
                            <select
                                id="payment_method"
                                name="payment_method"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Semua metode</option>
                                <option value="cash" @selected($filters['payment_method'] === 'cash')>cash</option>
                                <option value="qr" @selected($filters['payment_method'] === 'qr')>qr</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <x-primary-button class="w-full justify-center">Filter</x-primary-button>
                            <a
                                href="{{ route('transactions.index') }}"
                                class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-100"
                            >
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Daftar Transaksi</h3>
                        <a
                            href="{{ route('transactions.create') }}"
                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Tambah Transaksi
                        </a>
                    </div>

                    @if ($transactions->isEmpty())
                        <div class="rounded-md border border-gray-200 bg-gray-50 px-4 py-6 text-sm text-gray-600">
                            Belum ada data transaksi.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Tanggal</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Pegawai</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Jumlah Layanan</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Jumlah Produk</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Metode Pembayaran</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Total Transaksi</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($transactions as $transaction)
                                        <tr>
                                            <td class="px-4 py-3">{{ $transaction->transaction_date?->locale('id')->translatedFormat('d F Y') }}</td>
                                            <td class="px-4 py-3">{{ $transaction->employee?->name ?? '-' }}</td>
                                            <td class="px-4 py-3">{{ $transaction->total_services }}</td>
                                            <td class="px-4 py-3">{{ $transaction->total_products }}</td>
                                            <td class="px-4 py-3 uppercase">{{ $transaction->payment_method }}</td>
                                            <td class="px-4 py-3">Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <a
                                                        href="{{ route('transactions.show', $transaction->id) }}"
                                                        class="inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-indigo-700 transition hover:bg-indigo-100"
                                                    >
                                                        Detail
                                                    </a>

                                                    <a
                                                        href="{{ route('transactions.edit', $transaction->id) }}"
                                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-100"
                                                    >
                                                        Edit
                                                    </a>

                                                    <x-delete-form
                                                        :action="route('transactions.destroy', $transaction->id)"
                                                        button-text="Hapus"
                                                        confirm-message="Yakin ingin menghapus data ini?"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $transactions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
