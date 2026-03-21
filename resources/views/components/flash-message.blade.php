@php
    $alerts = [
        'success' => [
            'label' => 'Berhasil',
            'container' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
            'badge' => 'bg-emerald-100 text-emerald-700',
        ],
        'warning' => [
            'label' => 'Perhatian',
            'container' => 'border-amber-300 bg-amber-50 text-amber-950',
            'badge' => 'bg-amber-100 text-amber-800',
        ],
        'error' => [
            'label' => 'Perlu dicek',
            'container' => 'border-red-200 bg-red-50 text-red-900',
            'badge' => 'bg-red-100 text-red-700',
        ],
    ];
@endphp

@if (session('success') || session('error') || session('warning'))
    <div class="mb-6 space-y-3">
        @foreach ($alerts as $key => $alert)
            @if (session($key))
                <div class="rounded-2xl border px-4 py-3 {{ $alert['container'] }}">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $alert['badge'] }}">
                            {{ $alert['label'] }}
                        </span>
                        <p class="text-sm leading-6">{{ session($key) }}</p>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif
