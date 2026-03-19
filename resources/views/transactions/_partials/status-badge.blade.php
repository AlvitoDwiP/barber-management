@props(['transaction'])

@php
    $payrollStatus = $transaction->payrollPeriod?->status;

    $label = match ($payrollStatus) {
        \App\Models\PayrollPeriod::STATUS_CLOSED => 'Payroll Closed',
        \App\Models\PayrollPeriod::STATUS_OPEN => 'Payroll Open',
        default => 'Belum Payroll',
    };

    $classes = match ($payrollStatus) {
        \App\Models\PayrollPeriod::STATUS_CLOSED => 'transaction-status-badge-closed',
        \App\Models\PayrollPeriod::STATUS_OPEN => 'transaction-status-badge-open',
        default => 'transaction-status-badge-draft',
    };
@endphp

<span {{ $attributes->class(['transaction-status-badge', $classes]) }}>
    {{ $label }}
</span>
