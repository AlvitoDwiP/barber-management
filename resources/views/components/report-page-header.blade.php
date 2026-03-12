@props([
    'title',
    'backUrl' => null,
])

@php
    $resolvedBackUrl = $backUrl;

    if (! $resolvedBackUrl) {
        $resolvedBackUrl = \Illuminate\Support\Facades\Route::has('reports.index')
            ? route('reports.index')
            : url()->previous();
    }
@endphp

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ $title }}</h2>

    <a
        href="{{ $resolvedBackUrl }}"
        class="inline-flex w-fit items-center gap-2 self-start rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
    >
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="M11.667 5 6.667 10l5 5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
        </svg>
        <span>Kembali</span>
    </a>
</div>
