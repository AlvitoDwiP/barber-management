@props([
    'title',
    'createUrl' => null,
    'createLabel' => 'Tambah Data',
])

<div class="mb-4 flex items-center justify-between gap-3">
    <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>

    @if ($createUrl)
        <a
            href="{{ $createUrl }}"
            class="inline-flex items-center rounded-md border border-[#934C2D] bg-[#934C2D] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:border-[#7D4026] hover:bg-[#7D4026] focus:outline-none focus:ring-2 focus:ring-[#A85F3B] focus:ring-offset-2"
        >
            {{ $createLabel }}
        </a>
    @endif
</div>
