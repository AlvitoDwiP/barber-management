@props([
    'transaction',
    'title' => 'Transaksi terkunci payroll',
    'message' => 'Transaksi ini sudah masuk payroll final yang berstatus closed, sehingga perubahan dan penghapusan tidak diizinkan.',
])

@php
    $payrollPeriod = $transaction->payrollPeriod;
    $isLocked = $payrollPeriod?->status === \App\Models\PayrollPeriod::STATUS_CLOSED;
@endphp

@if ($isLocked)
    <div {{ $attributes->class(['transaction-alert transaction-alert-lock']) }}>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#7D4026]">{{ $title }}</p>
                <p class="mt-1 text-sm leading-6 text-[#8B533B]">{{ $message }}</p>
            </div>

            @if ($payrollPeriod)
                <div class="rounded-xl bg-white/70 px-4 py-3 text-xs font-medium text-[#7D4026]">
                    Payroll:
                    {{ $payrollPeriod->start_date?->locale('id')->translatedFormat('d M Y') ?? '-' }}
                    -
                    {{ $payrollPeriod->end_date?->locale('id')->translatedFormat('d M Y') ?? '-' }}
                </div>
            @endif
        </div>
    </div>
@endif
