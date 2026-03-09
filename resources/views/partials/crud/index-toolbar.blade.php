@props([
    'title',
    'createUrl',
    'createLabel',
])

<div class="mb-4 flex items-center justify-between">
    <h3 class="text-lg font-semibold">{{ $title }}</h3>
    <a
        href="{{ $createUrl }}"
        class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
    >
        {{ $createLabel }}
    </a>
</div>
