@props([
    'action',
    'confirmMessage' => 'Yakin ingin menghapus data ini?',
    'buttonText' => 'Hapus',
    'variant' => 'soft-danger',
])

@php
    $baseClass = 'inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-widest transition focus:outline-none focus:ring-2 focus:ring-offset-2';
    $variantClass = match ($variant) {
        'solid-danger' => 'border border-transparent bg-red-600 text-white hover:bg-red-500 focus:ring-red-500',
        default => 'border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 focus:ring-red-400',
    };
@endphp

<form method="POST" action="{{ $action }}" class="inline" onsubmit="return confirm(@js($confirmMessage));">
    @csrf
    @method('DELETE')

    <button
        type="submit"
        {{ $attributes->class([$baseClass, $variantClass]) }}
    >
        {{ $buttonText }}
    </button>
</form>
