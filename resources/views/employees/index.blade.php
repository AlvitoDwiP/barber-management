<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pegawai') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include('partials.crud.index-toolbar', [
                        'title' => 'Daftar Pegawai',
                        'createUrl' => route('employees.create'),
                        'createLabel' => 'Tambah Pegawai',
                    ])

                    @if ($employees->isEmpty())
                        @include('partials.crud.empty-state', [
                            'message' => 'Belum ada data pegawai.',
                        ])
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Nama</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Jenis Pegawai</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($employees as $employee)
                                        <tr>
                                            <td class="px-4 py-3">{{ $employee->name }}</td>
                                            <td class="px-4 py-3">{{ $employee->employment_type_label }}</td>
                                            <td class="px-4 py-3">
                                                @include('partials.crud.action-buttons', [
                                                    'editUrl' => route('employees.edit', $employee),
                                                    'deleteUrl' => route('employees.destroy', $employee),
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
