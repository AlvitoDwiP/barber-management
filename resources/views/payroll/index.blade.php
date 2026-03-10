<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Penggajian') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="admin-card">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Periode Payroll</h3>
                    <p class="text-sm text-slate-500">Daftar periode payroll berdasarkan tanggal mulai terbaru.</p>
                </div>

                <form action="{{ route('payroll.open') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-brand-primary">
                        Open Payroll
                    </button>
                </form>
            </div>

            @if ($payrollPeriods->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-600">
                    Belum ada periode payroll.
                </div>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table w-full min-w-[760px]">
                        <thead>
                            <tr>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($payrollPeriods as $period)
                                <tr class="hover:bg-slate-50/70">
                                    <td>{{ $period->start_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}</td>
                                    <td>{{ $period->end_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}</td>
                                    <td>
                                        <span class="payment-badge {{ $period->status === 'open' ? 'payment-badge-qr' : 'payment-badge-cash' }}">
                                            {{ $period->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('payroll.show', $period) }}" class="btn-brand-soft">
                                                Detail
                                            </a>

                                            <form action="{{ route('payroll.open') }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn-brand-primary">
                                                    Open Payroll
                                                </button>
                                            </form>

                                            <a
                                                href="#"
                                                class="inline-flex items-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-red-500"
                                            >
                                                Close Payroll
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{ $payrollPeriods->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
