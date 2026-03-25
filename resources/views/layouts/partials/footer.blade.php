@php
    $guest = $guest ?? false;
    $appName = config('app.name', 'Barber Management');
@endphp

<footer class="{{ $guest ? 'border-t border-slate-200/80 bg-white/80' : 'border-t border-slate-200/80 bg-white/70' }}">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-4 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <div class="space-y-1">
            <p>Sejati Hair Studio.</p>
        </div>

        <div class="space-y-1 sm:text-right">
            <p>&copy; Ver 1. {{ now()->year }}</p>
        </div>
    </div>
</footer>
