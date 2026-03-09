@props([
    'submitLabel',
    'cancelUrl',
])

<div class="flex items-center gap-3 pt-2">
    <x-primary-button>
        {{ $submitLabel }}
    </x-primary-button>

    <a
        href="{{ $cancelUrl }}"
        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-100"
    >
        Batal
    </a>
</div>
