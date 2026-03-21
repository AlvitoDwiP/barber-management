@props([
    'transaction',
    'title' => 'Transaksi sudah final',
    'message' => 'Transaksi ini sudah masuk payroll yang ditutup. Detail masih bisa dilihat, tetapi edit dan hapus sudah dinonaktifkan.',
])

@php
    $payrollPeriod = $transaction->payrollPeriod;
    $isLocked = $payrollPeriod?->status === \App\Models\PayrollPeriod::STATUS_CLOSED;
    $payrollRangeLabel = $payrollPeriod
        ? ($payrollPeriod->start_date?->locale('id')->translatedFormat('d M Y') ?? '-')
            .' - '
            .($payrollPeriod->end_date?->locale('id')->translatedFormat('d M Y') ?? '-')
        : null;
@endphp

@if ($isLocked)
    <div {{ $attributes->class(['transaction-alert transaction-alert-lock']) }}>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-[#7D4026]">{{ $title }}</p>
                <p class="mt-1 text-sm leading-6 text-[#8B533B]">{{ $message }}</p>
            </div>

            @if ($payrollRangeLabel)
                <div class="rounded-xl border border-[#F0D8AE] bg-white/80 px-3 py-2 text-xs font-medium text-[#7D4026]">
                    Payroll final: {{ $payrollRangeLabel }}
                </div>
            @endif
        </div>
    </div>
@endif
