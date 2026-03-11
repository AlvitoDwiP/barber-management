<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Penggajian') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <section class="admin-card">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-slate-900">Periode Payroll</h3>
                <p class="text-sm text-slate-500">Daftar periode payroll berdasarkan tanggal mulai terbaru.</p>
            </div>

            <form
                action="{{ route('payroll.open') }}"
                method="POST"
                class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4"
                x-data="{
                    requiresOverlapConfirm: @js($payrollOverlapWarning ?? false),
                    overlapConfirmed: @js(old('overlap_confirmation') === '1' || old('overlap_confirmation') === 1),
                }"
            >
                @csrf

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <x-input-label for="start_date" :value="__('Start Date')" />
                        <x-text-input
                            id="start_date"
                            name="start_date"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('start_date')"
                            data-flatpickr="date"
                            autocomplete="off"
                            required
                        />
                        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="end_date" :value="__('End Date')" />
                        <x-text-input
                            id="end_date"
                            name="end_date"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('end_date')"
                            data-flatpickr="date"
                            autocomplete="off"
                            required
                        />
                        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="btn-brand-primary w-full justify-center disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="requiresOverlapConfirm && !overlapConfirmed"
                        >
                            Open Payroll
                        </button>
                    </div>
                </div>

                @if ($payrollOverlapWarning ?? false)
                    <div class="mt-4 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                        Periode payroll ini bertabrakan dengan payroll lain. Hal ini dapat menyebabkan transaksi dihitung lebih dari satu kali.

                        <label class="mt-3 flex items-start gap-2">
                            <input
                                type="checkbox"
                                name="overlap_confirmation"
                                value="1"
                                x-model="overlapConfirmed"
                                class="mt-1 rounded border-slate-300 text-[#934C2D] shadow-sm focus:ring-[#A85F3B]"
                            />
                            <span>Saya memahami bahwa payroll ini overlap dengan payroll lain dan tetap ingin melanjutkan.</span>
                        </label>
                    </div>
                @endif
            </form>

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

                                            @if ($period->status === 'open')
                                                <form action="{{ route('payroll.close', $period) }}" method="POST">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-red-500"
                                                    >
                                                        Close Payroll
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                                    Sudah ditutup
                                                </span>
                                            @endif
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
