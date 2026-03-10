@props([
    'submitLabel',
    'cancelUrl',
])

<div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center">
    <a
        href="{{ $cancelUrl }}"
        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-slate-700 transition hover:bg-slate-100"
    >
        Batal
    </a>

    <x-primary-button>
        {{ $submitLabel }}
    </x-primary-button>
</div>
