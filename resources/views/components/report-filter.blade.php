@props([
    'action' => url()->current(),
    'showDateRange' => true,
    'showYear' => false,
    'startDate' => request('start_date'),
    'endDate' => request('end_date'),
    'year' => request('year', now()->year),
])

<form method="GET" action="{{ $action }}" class="admin-card">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        @if ($showDateRange)
            <div>
                <label for="start_date" class="text-sm font-medium text-slate-700">Tanggal mulai</label>
                <input
                    id="start_date"
                    name="start_date"
                    type="date"
                    value="{{ $startDate }}"
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
                    type="date"
                    value="{{ $endDate }}"
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

    <div class="mt-4 flex flex-wrap items-center gap-3">
        <button type="submit" class="inline-flex items-center rounded-lg bg-[#A85F3B] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#934C2D]">
            Terapkan Filter
        </button>
        <a href="{{ $action }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            Reset
        </a>
    </div>
</form>
