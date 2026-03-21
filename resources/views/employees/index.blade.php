<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Pegawai') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="admin-card">
            @include('partials.crud.index-toolbar', [
                'title' => 'Daftar Pegawai',
                'description' => 'Kelola barber dan pegawai aktif agar pilihan di transaksi, payroll, dan laporan tetap rapi.',
                'count' => $employees->count(),
                'createUrl' => route('employees.create'),
                'createLabel' => 'Tambah Pegawai',
            ])

            @if ($employees->isEmpty())
                @include('partials.crud.empty-state', [
                    'title' => 'Belum ada pegawai',
                    'message' => 'Tambahkan pegawai terlebih dahulu supaya transaksi, payroll, dan laporan bisa terhubung ke orang yang tepat.',
                    'actionUrl' => route('employees.create'),
                    'actionLabel' => 'Tambah Pegawai',
                ])
            @else
                <p class="mb-4 text-sm text-slate-600">
                    Pegawai nonaktif tetap disimpan bila sudah punya histori transaksi atau payroll.
                </p>

                <div class="admin-table-wrap">
                    <table class="admin-table w-full min-w-[760px]">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Jenis Pegawai</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($employees as $employee)
                                <tr class="hover:bg-slate-50/70">
                                    <td>
                                        <div class="font-medium text-slate-900">{{ $employee->name }}</div>
                                    </td>
                                    <td>{{ $employee->employment_type_label }}</td>
                                    <td>
                                        <span class="payment-badge {{ $employee->isActive() ? 'payment-badge-qr' : 'payment-badge-cash' }}">
                                            {{ $employee->operationalStatusLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('employees.edit', $employee) }}" class="btn-brand-soft">
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
                                                    confirm-message="Pegawai ini punya histori. Lanjutkan untuk menonaktifkan pegawai?"
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
        </section>
    </div>
</x-app-layout>
