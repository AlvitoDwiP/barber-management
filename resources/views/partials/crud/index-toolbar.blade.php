@props([
    'title',
    'description' => null,
    'count' => null,
    'createUrl' => null,
    'createLabel' => 'Tambah Data',
])

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div class="space-y-1">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>

            @if ($count !== null)
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                    {{ number_format((int) $count, 0, ',', '.') }} data
                </span>
            @endif
        </div>

        @if ($description)
            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                {{ $description }}
            </p>
        @endif
    </div>

    @if ($createUrl)
        <a href="{{ $createUrl }}" class="btn-brand-primary w-full justify-center sm:w-auto">
            {{ $createLabel }}
        </a>
    @endif
</div>
