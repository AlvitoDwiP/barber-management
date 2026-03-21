<x-app-layout>
    @php
        $closePayrollConfirmMessage = "Anda akan menutup payroll untuk periode ini.\nSemua transaksi dalam periode payroll akan dikunci dan tidak dapat dihitung ulang.\nApakah Anda yakin ingin menutup payroll ini?";
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold leading-tight text-slate-900">Detail Payroll</h2>
            @include('payroll._tabs')
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="admin-card">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">Ringkasan Periode Payroll</h1>
                    <p class="mt-1 text-sm text-slate-600">Tinjau periode, status, dan hasil komisi pegawai dari satu halaman.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($payrollPeriod->status === \App\Models\PayrollPeriod::STATUS_OPEN)
                        <form
                            action="{{ route('payroll.close', $payrollPeriod) }}"
                            method="POST"
                            onsubmit="return confirm(@js($closePayrollConfirmMessage))"
                        >
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-red-500"
                            >
                                Tutup Payroll
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('payroll.index') }}" class="btn-neutral-warm">
                        Kembali ke Daftar Payroll
                    </a>
                </div>
            </div>

            @if ($payrollPeriod->status === \App\Models\PayrollPeriod::STATUS_OPEN)
                <div class="mb-4 space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                    <p>Jumlah transaksi dalam payroll ini: <span class="font-semibold">{{ (int) $transactionCount }} transaksi</span></p>
                    @if ((int) $transactionCount === 0)
                        <p class="font-medium text-amber-700">Tidak ada transaksi dalam periode payroll ini.</p>
                    @endif
                    <p class="text-slate-600">Rincian pegawai di bawah ini masih berupa pratinjau langsung dari snapshot transaksi yang belum masuk payroll.</p>
                </div>
            @endif

            <div class="admin-table-wrap">
                <table class="admin-table w-full min-w-[640px]">
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr>
                            <th>Tanggal Mulai</th>
                            <td>{{ $payrollPeriod->start_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Selesai</th>
                            <td>{{ $payrollPeriod->end_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="payment-badge {{ $payrollPeriod->status === \App\Models\PayrollPeriod::STATUS_OPEN ? 'payment-badge-qr' : 'payment-badge-cash' }}">
                                    {{ $payrollPeriod->status === \App\Models\PayrollPeriod::STATUS_OPEN ? 'Terbuka' : 'Ditutup' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Ditutup Pada</th>
                            <td>{{ $payrollPeriod->closed_at?->locale('id')->translatedFormat('d F Y H:i') ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if ($payrollPeriod->status === \App\Models\PayrollPeriod::STATUS_CLOSED)
                <p class="mt-3 text-sm text-slate-600">
                    Payroll ini sudah ditutup dan detail di bawah dibaca dari snapshot final hasil payroll.
                </p>
            @endif
        </section>

        <section class="admin-card">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Hasil Payroll per Pegawai</h2>

            @if ($payrollRows->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-600">
                    @if ($payrollPeriod->status === \App\Models\PayrollPeriod::STATUS_CLOSED)
                        Payroll ini tidak memiliki snapshot hasil per pegawai.
                    @else
                        Belum ada transaksi yang bisa ditampilkan untuk pratinjau payroll ini.
                    @endif
                </div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table w-full min-w-[1100px]">
                        <thead>
                            <tr>
                                <th>Nama Pegawai</th>
                                <th>Total Transaksi</th>
                                <th>Jumlah Layanan</th>
                                <th>Nominal Layanan</th>
                                <th>Komisi Layanan</th>
                                <th>Jumlah Produk</th>
                                <th>Komisi Produk</th>
                                <th>Total Komisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($payrollRows as $result)
                                <tr class="hover:bg-slate-50/70">
                                    <td>{{ $result->employee_name ?? '-' }}</td>
                                    <td>{{ (int) $result->total_transaction_count }}</td>
                                    <td>{{ (int) $result->total_services }}</td>
                                    <td>Rp {{ number_format((float) $result->total_service_amount, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format((float) $result->total_service_commission, 0, ',', '.') }}</td>
                                    <td>{{ (int) $result->total_products }}</td>
                                    <td>Rp {{ number_format((float) $result->total_product_commission, 0, ',', '.') }}</td>
                                    <td class="font-semibold text-slate-900">
                                        Rp {{ number_format((float) $result->total_commission, 0, ',', '.') }}
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
