<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pengeluaran') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Daftar Pengeluaran</h3>
                        <a
                            href="{{ route('expenses.create') }}"
                            class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Tambah Pengeluaran
                        </a>
                    </div>

                    @if ($expenses->isEmpty())
                        <div class="rounded-md border border-gray-200 bg-gray-50 px-4 py-6 text-sm text-gray-600">
                            Belum ada data pengeluaran.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Tanggal</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Kategori</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Jumlah</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Catatan</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($expenses as $expense)
                                        <tr>
                                            <td class="px-4 py-3">{{ $expense->expense_date?->locale('id')->translatedFormat('d F Y') }}</td>
                                            <td class="px-4 py-3">{{ $expense->category }}</td>
                                            <td class="px-4 py-3">Rp {{ number_format((float) $expense->amount, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3">{{ filled($expense->note) ? $expense->note : '-' }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <a
                                                        href="{{ route('expenses.edit', $expense) }}"
                                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-100"
                                                    >
                                                        Edit
                                                    </a>

                                                    <x-delete-form
                                                        :action="route('expenses.destroy', $expense)"
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
