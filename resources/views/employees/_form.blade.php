@php
    $nameValue = old('name', $employee?->name);
    $statusValue = old('status', $employee?->status);
@endphp

<div>
    <x-input-label for="name" :value="__('Nama')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$nameValue" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="status" :value="__('Status')" />
    <select
        id="status"
        name="status"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        required
    >
        <option value="">Pilih status</option>
        <option value="tetap" @selected($statusValue === 'tetap')>tetap</option>
        <option value="freelance" @selected($statusValue === 'freelance')>freelance</option>
    </select>
    <x-input-error :messages="$errors->get('status')" class="mt-2" />
</div>

<div class="flex items-center gap-3 pt-2">
    <x-primary-button>
        {{ $submitLabel }}
    </x-primary-button>

    <a
        href="{{ route('employees.index') }}"
        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-100"
    >
        Batal
    </a>
</div>
