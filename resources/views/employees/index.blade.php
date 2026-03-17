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
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($employees as $employee)
                                        <tr>
                                            <td class="px-4 py-3">{{ $employee->name }}</td>
                                            <td class="px-4 py-3">{{ $employee->employment_type_label }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $employee->isActive() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                                    {{ $employee->operationalStatusLabel() }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <a
                                                        href="{{ route('employees.edit', $employee) }}"
                                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-100"
                                                    >
                                                        Edit
                                                    </a>

                                                    @if ($employee->canBeDeletedPhysically())
                                                        <x-delete-form
                                                            :action="route('employees.destroy', $employee)"
                                                            button-text="Hapus"
                                                            confirm-message="Yakin ingin menghapus pegawai ini?"
                                                        />
                                                    @elseif ($employee->isActive())
                                                        <x-delete-form
                                                            :action="route('employees.destroy', $employee)"
                                                            button-text="Nonaktifkan"
                                                            confirm-message="Pegawai ini memiliki histori. Lanjutkan untuk menonaktifkan pegawai?"
                                                        />
                                                    @else
                                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-slate-500">
                                                            Sudah Nonaktif
                                                        </span>
                                                    @endif
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
