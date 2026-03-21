@props([
    'action' => url()->current(),
    'showDateRange' => true,
    'showYear' => false,
    'startDateField' => 'start_date',
    'endDateField' => 'end_date',
    'startDateLabel' => 'Tanggal mulai',
    'endDateLabel' => 'Tanggal akhir',
    'startDate' => null,
    'endDate' => null,
    'year' => request('year', now()->year),
    'filterLabel' => 'Filter Laporan',
    'filterKeys' => [],
    'helperText' => 'Filter memengaruhi tampilan tabel dan file export pada halaman ini.',
])

@php
    $startDate ??= request($startDateField);
    $endDate ??= request($endDateField);
    $resolvedFilterKeys = collect($filterKeys);

    if ($resolvedFilterKeys->isEmpty()) {
        if ($showDateRange) {
            $resolvedFilterKeys = $resolvedFilterKeys->merge([$startDateField, $endDateField]);
        }

        if ($showYear) {
            $resolvedFilterKeys = $resolvedFilterKeys->push('year');
        }
    }

    $hasActiveFilters = $resolvedFilterKeys
        ->filter(fn ($key) => filled(request($key)))
        ->isNotEmpty();

    $filterId = 'report-filter-'.md5($action.'|'.$resolvedFilterKeys->implode('|'));
@endphp

<section class="admin-card p-4 sm:p-5" x-data="{ filterOpen: @js($hasActiveFilters), closedLabel: @js($filterLabel) }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900">{{ $filterLabel }}</h3>
            <p class="text-sm text-slate-500">{{ $helperText }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @isset($actions)
                {{ $actions }}
            @endisset

            <button
                type="button"
                class="btn-neutral-warm justify-center"
                @click="filterOpen = !filterOpen"
                :aria-expanded="filterOpen.toString()"
                aria-controls="{{ $filterId }}"
            >
                <span x-text="filterOpen ? 'Tutup Filter' : closedLabel"></span>
            </button>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide">
        @if ($hasActiveFilters)
            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-emerald-700">
                Filter aktif
            </span>
            <span class="text-slate-500">Tabel dan export mengikuti filter yang dipilih.</span>
        @else
            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-slate-600">
                Filter default
            </span>
            <span class="text-slate-500">Gunakan filter saat perlu melihat periode atau fokus tertentu.</span>
        @endif
    </div>

    <div
        id="{{ $filterId }}"
        x-cloak
        x-show="filterOpen"
        x-transition.opacity.duration.150ms
        class="mt-5 border-t border-slate-200 pt-5"
    >
        <form method="GET" action="{{ $action }}">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                @if ($showDateRange)
                    <div>
                        <label for="{{ $startDateField }}" class="text-sm font-medium text-slate-700">{{ $startDateLabel }}</label>
                        <input
                            id="{{ $startDateField }}"
                            name="{{ $startDateField }}"
                            type="text"
                            value="{{ $startDate }}"
                            data-flatpickr="date"
                            autocomplete="off"
                            class="form-brand-control"
                        />
                        @error($startDateField)
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="{{ $endDateField }}" class="text-sm font-medium text-slate-700">{{ $endDateLabel }}</label>
                        <input
                            id="{{ $endDateField }}"
                            name="{{ $endDateField }}"
                            type="text"
                            value="{{ $endDate }}"
                            data-flatpickr="date"
                            autocomplete="off"
                            class="form-brand-control"
                        />
                        @error($endDateField)
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                @if ($showYear)
                    <div>
                        <label for="year" class="text-sm font-medium text-slate-700">Tahun</label>
                        <input
                            id="year"
                            name="year"
                            type="number"
                            min="2000"
                            max="{{ now()->year + 1 }}"
                            value="{{ $year }}"
                            class="form-brand-control"
                        />
                    </div>
                @endif

                {{ $slot }}
            </div>

            <div class="mt-4 flex w-full flex-wrap items-center justify-start gap-3">
                <x-primary-button class="shrink-0">
                    Filter
                </x-primary-button>
                <a href="{{ $action }}" class="btn-neutral-warm shrink-0">
                    Reset
                </a>
            </div>
        </form>
    </div>
</section>
