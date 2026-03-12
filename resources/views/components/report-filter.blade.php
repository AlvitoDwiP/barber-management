@props([
    'action' => url()->current(),
    'showDateRange' => true,
    'showYear' => false,
    'startDate' => request('start_date'),
    'endDate' => request('end_date'),
    'year' => request('year', now()->year),
    'filterLabel' => 'Filter Laporan',
    'filterKeys' => [],
])

@php
    $resolvedFilterKeys = collect($filterKeys);

    if ($resolvedFilterKeys->isEmpty()) {
        if ($showDateRange) {
            $resolvedFilterKeys = $resolvedFilterKeys->merge(['start_date', 'end_date']);
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

<section class="admin-card" x-data="{ filterOpen: @js($hasActiveFilters), closedLabel: @js($filterLabel) }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900">{{ $filterLabel }}</h3>
            <p class="text-sm text-slate-500">Buka filter saat diperlukan untuk menyaring data laporan.</p>
        </div>

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
                        <label for="start_date" class="text-sm font-medium text-slate-700">Tanggal mulai</label>
                        <input
                            id="start_date"
                            name="start_date"
                            type="text"
                            value="{{ $startDate }}"
                            data-flatpickr="date"
                            autocomplete="off"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
                        />
                        @error('start_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="end_date" class="text-sm font-medium text-slate-700">Tanggal akhir</label>
                        <input
                            id="end_date"
                            name="end_date"
                            type="text"
                            value="{{ $endDate }}"
                            data-flatpickr="date"
                            autocomplete="off"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
                        />
                        @error('end_date')
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
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
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
