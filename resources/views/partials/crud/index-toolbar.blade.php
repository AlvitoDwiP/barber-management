@props([
    'title',
    'createUrl' => null,
    'createLabel' => 'Tambah Data',
])

<div class="mb-4 flex items-center justify-between gap-3">
    <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>

    @if ($createUrl)
        <a href="{{ $createUrl }}" class="btn-brand-primary">
            {{ $createLabel }}
        </a>
    @endif
</div>
