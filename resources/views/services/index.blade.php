<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Layanan') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('partials.crud.index-toolbar', [
                        'title' => 'Daftar Layanan',
                        'createUrl' => route('services.create'),
                        'createLabel' => 'Tambah Layanan',
                    ])

                    @if ($services->isEmpty())
                        @include('partials.crud.empty-state', [
                            'message' => 'Belum ada data layanan.',
                        ])
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Harga</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($services as $service)
                                        <tr>
                                            <td class="px-4 py-3">{{ $service->name }}</td>
                                            <td class="px-4 py-3">Rp {{ number_format((float) $service->price, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3">
                                                @include('partials.crud.action-buttons', [
                                                    'editUrl' => route('services.edit', $service),
                                                    'deleteUrl' => route('services.destroy', $service),
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
