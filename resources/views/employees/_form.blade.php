@php
    $nameValue = old('name', $employee?->name);
    $employmentTypeValue = old('employment_type', $employee?->employment_type);
@endphp

<div>
    <x-input-label for="name" :value="__('Nama')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$nameValue" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="employment_type" :value="__('Jenis Pegawai')" />
    <select
        id="employment_type"
        name="employment_type"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        required
    >
        <option value="">Pilih jenis pegawai</option>
        <option value="permanent" @selected($employmentTypeValue === 'permanent')>Permanent</option>
        <option value="freelance" @selected($employmentTypeValue === 'freelance')>Freelance</option>
    </select>
    <x-input-error :messages="$errors->get('employment_type')" class="mt-2" />
</div>

@include('partials.crud.form-actions', [
    'submitLabel' => $submitLabel,
    'cancelUrl' => route('employees.index'),
])
