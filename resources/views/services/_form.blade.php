@php
    $nameValue = old('name', $service?->name);
    $priceValue = old('price', $service?->price);
    $commissionTypeValue = old('commission_type', $service?->commission_type);
    $commissionValue = old('commission_value', $service?->commission_value);
@endphp

<div>
    <x-input-label for="name" :value="__('Nama')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$nameValue" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="price" :value="__('Harga')" />
    <x-text-input id="price" name="price" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="$priceValue" required />
    <x-input-error :messages="$errors->get('price')" class="mt-2" />
</div>

<div class="rounded-lg border border-slate-200 p-4 space-y-4">
    <div>
        <h3 class="text-sm font-semibold text-slate-900">Override Komisi</h3>
        <p class="mt-1 text-sm text-slate-600">Kosongkan tipe dan nilai jika layanan ini harus mengikuti default global.</p>
    </div>

    <div>
        <x-input-label for="commission_type" :value="__('Tipe Komisi')" />
        <select
            id="commission_type"
            name="commission_type"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
        >
            <option value="">Ikuti Default Global</option>
            <option value="percent" @selected($commissionTypeValue === 'percent')>Persen (%)</option>
        </select>
        <x-input-error :messages="$errors->get('commission_type')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="commission_value" :value="__('Nilai Komisi')" />
        <x-text-input
            id="commission_value"
            name="commission_value"
            type="number"
            step="0.01"
            min="0"
            class="mt-1 block w-full"
            :value="$commissionValue"
        />
        <p class="mt-1 text-sm text-slate-500">Gunakan nilai 0 sampai 100 untuk komisi berbasis Persen (%).</p>
        <x-input-error :messages="$errors->get('commission_value')" class="mt-2" />
    </div>
</div>

@include('partials.crud.form-actions', [
    'submitLabel' => $submitLabel,
    'cancelUrl' => route('services.index'),
])
