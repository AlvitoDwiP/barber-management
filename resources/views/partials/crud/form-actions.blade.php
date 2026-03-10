@props([
    'submitLabel',
    'cancelUrl',
])

<div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center">
    <a href="{{ $cancelUrl }}" class="btn-neutral-warm justify-center">
        Batal
    </a>

    <x-primary-button>
        {{ $submitLabel }}
    </x-primary-button>
</div>
