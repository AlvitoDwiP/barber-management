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
                    @include('partials.crud.index-toolbar', [
                        'title' => 'Daftar Pengeluaran',
                        'createUrl' => route('expenses.create'),
                        'createLabel' => 'Tambah Pengeluaran',
                    ])

                    @if ($expenses->isEmpty())
                        @include('partials.crud.empty-state', [
                            'message' => 'Belum ada data pengeluaran.',
                        ])
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
                                                @include('partials.crud.action-buttons', [
                                                    'editUrl' => route('expenses.edit', $expense),
                                                    'deleteUrl' => route('expenses.destroy', $expense),
                                                ])
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
