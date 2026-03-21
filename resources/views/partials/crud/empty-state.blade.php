@props([
    'title' => 'Belum ada data',
    'message',
    'actionUrl' => null,
    'actionLabel' => null,
])

<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-600">
    <div class="space-y-2">
        <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
        <p class="max-w-2xl leading-6">{{ $message }}</p>

        @if ($actionUrl && $actionLabel)
            <div class="pt-2">
                <a href="{{ $actionUrl }}" class="btn-brand-primary w-full justify-center sm:w-auto">
                    {{ $actionLabel }}
                </a>
            </div>
        @endif
    </div>
</div>
