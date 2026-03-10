<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Payroll Detail') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="admin-card">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-slate-900">Detail Payroll Period</h1>
                <div class="flex items-center gap-2">
                    @if ($payrollPeriod->status === 'open')
                        <form action="{{ route('payroll.close', $payrollPeriod) }}" method="POST">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-red-500"
                            >
                                Close Payroll
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('payroll.index') }}" class="btn-neutral-warm">
                        Kembali ke Daftar Payroll
                    </a>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table w-full min-w-[640px]">
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr>
                            <th>Start Date</th>
                            <td>{{ $payrollPeriod->start_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>End Date</th>
                            <td>{{ $payrollPeriod->end_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="payment-badge {{ $payrollPeriod->status === 'open' ? 'payment-badge-qr' : 'payment-badge-cash' }}">
                                    {{ ucfirst($payrollPeriod->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Closed At</th>
                            <td>{{ $payrollPeriod->closed_at?->locale('id')->translatedFormat('d F Y H:i') ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if ($payrollPeriod->status === 'closed')
                <p class="mt-3 text-sm text-slate-600">
                    Payroll ini sudah ditutup dan hasilnya bersifat final.
                </p>
            @endif
        </section>

        <section class="admin-card">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Hasil Payroll per Pegawai</h2>

            @if ($payrollPeriod->payrollResults->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-600">
                    Payroll ini belum memiliki hasil perhitungan. Hasil payroll akan tersedia setelah payroll ditutup.
                </div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table w-full min-w-[860px]">
                        <thead>
                            <tr>
                                <th>Nama Pegawai</th>
                                <th>Total Transaksi</th>
                                <th>Total Layanan</th>
                                <th>Total Produk</th>
                                <th>Total Komisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($payrollPeriod->payrollResults as $result)
                                <tr class="hover:bg-slate-50/70">
                                    <td>{{ $result->employee?->name ?? '-' }}</td>
                                    <td>{{ (int) $result->total_transactions }}</td>
                                    <td>{{ (int) $result->total_services }}</td>
                                    <td>{{ (int) $result->total_products }}</td>
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
