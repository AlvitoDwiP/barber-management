<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Penggajian') }}</h2>
            @include('payroll._tabs')
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-report-filter
            :action="route('payroll.freelance.index')"
            filterLabel="Filter Komisi Freelance"
            :startDate="$filters['start_date']"
            :endDate="$filters['end_date']"
            :filterKeys="['start_date', 'end_date', 'employee_id']"
        >
            <div>
                <label for="employee_id" class="text-sm font-medium text-slate-700">Pegawai freelance</label>
                <select
                    id="employee_id"
                    name="employee_id"
                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
                >
                    <option value="">Semua pegawai freelance</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) $filters['employee_id'] === (string) $employee->id)>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </x-report-filter>

        <section class="admin-card">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-slate-900">Komisi Freelance Harian</h3>
                <p class="text-sm text-slate-500">
                    Rekap komisi harian untuk pegawai freelance berdasarkan transaksi valid pada tanggal kerja masing-masing.
                </p>
            </div>

            @if ($rows->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-600">
                    Belum ada komisi freelance pada filter tanggal yang dipilih.
                </div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table w-full min-w-[1120px]">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama Pegawai</th>
                                <th>Total Layanan</th>
                                <th>Komisi Layanan</th>
                                <th>Jumlah Produk</th>
                                <th>Komisi Produk</th>
                                <th>Total Komisi</th>
                                <th>Status Pembayaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($rows as $row)
                                <tr class="hover:bg-slate-50/70">
                                    <td>{{ \Illuminate\Support\Carbon::parse($row->work_date)->locale('id')->translatedFormat('d F Y') }}</td>
                                    <td>{{ $row->employee_name }}</td>
                                    <td>{{ format_rupiah($row->total_service_amount) }}</td>
                                    <td>{{ format_rupiah($row->service_commission) }}</td>
                                    <td>{{ (int) $row->total_product_qty }}</td>
                                    <td>{{ format_rupiah($row->product_commission) }}</td>
                                    <td class="font-semibold text-slate-900">{{ format_rupiah($row->total_commission) }}</td>
                                    <td>
                                        @if ($row->payment_status === \App\Models\FreelancePayment::STATUS_PAID)
                                            <span class="payment-badge payment-badge-cash">Sudah Dibayar</span>
                                        @else
                                            <span class="payment-badge payment-badge-qr">Belum Dibayar</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row->payment_status === \App\Models\FreelancePayment::STATUS_PAID)
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                                                Sudah Dibayar
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('payroll.freelance.prepare-payment') }}">
                                                @csrf
                                                <input type="hidden" name="employee_id" value="{{ $row->employee_id }}">
                                                <input type="hidden" name="work_date" value="{{ $row->work_date }}">
                                                <button type="submit" class="btn-brand-primary">
                                                    Bayar Gaji
                                                </button>
                                            </form>
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
