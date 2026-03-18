@php
    $nameValue = old('name', $product?->name);
    $priceValue = old('price', $product?->price);
    $stockValue = old('stock', $product?->stock);
    $commissionTypeValue = old('commission_type', $product?->commission_type ?? '');
    $commissionValue = old('commission_value', $product?->commission_value ?? '');
    $isGlobalCommission = blank($commissionTypeValue);
    $customCommissionValue = $isGlobalCommission ? '' : $commissionValue;
    $displayCommissionValue = $isGlobalCommission ? $defaultCommissionValue : $customCommissionValue;
@endphp

<div>
    <x-input-label for="name" :value="__('Nama Produk')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$nameValue" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="price" :value="__('Harga Produk')" />
    <x-text-input id="price" name="price" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="$priceValue" required />
    <x-input-error :messages="$errors->get('price')" class="mt-2" />
</div>

<div>
    <x-input-label for="stock" :value="__('Stok')" />
    <x-text-input id="stock" name="stock" type="number" step="1" min="0" class="mt-1 block w-full" :value="$stockValue" required />
    <x-input-error :messages="$errors->get('stock')" class="mt-2" />
</div>

<div
    class="rounded-lg border border-slate-200 p-4 space-y-4"
    x-data="{
        commissionType: @js((string) $commissionTypeValue),
        defaultCommissionValue: @js((string) $defaultCommissionValue),
        customCommissionValue: @js((string) $customCommissionValue),
    }"
>
    <div>
        <h3 class="text-sm font-semibold text-slate-900">Override Komisi</h3>
        <p class="mt-1 text-sm text-slate-600">Kosongkan tipe dan nilai jika produk ini harus mengikuti default global.</p>
    </div>

    <div>
        <x-input-label for="commission_type" :value="__('Tipe Komisi')" />
        <select
            id="commission_type"
            name="commission_type"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
            x-model="commissionType"
        >
            <option value="">Global</option>
            <option value="percent" @selected($commissionTypeValue === 'percent')>Custom (Persen [%])</option>
            <option value="fixed" @selected($commissionTypeValue === 'fixed')>Custom (Rupiah [Rp])</option>
        </select>
        <x-input-error :messages="$errors->get('commission_type')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="commission_value" :value="__('Nilai Komisi')" />
        <input
            id="commission_value"
            type="number"
            step="0.01"
            min="0"
            class="border-gray-300 focus:border-[#A85F3B] focus:ring-[#A85F3B] rounded-md shadow-sm mt-1 block w-full"
            x-bind:class="commissionType === '' ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : ''"
            @if (! $isGlobalCommission)
                name="commission_value"
            @endif
            value="{{ $displayCommissionValue }}"
            @readonly($isGlobalCommission)
            x-bind:name="commissionType === '' ? null : 'commission_value'"
            x-bind:readonly="commissionType === ''"
            x-bind:value="commissionType === '' ? defaultCommissionValue : customCommissionValue"
            x-on:input="if (commissionType !== '') customCommissionValue = $event.target.value"
        >
        <input
            type="hidden"
            value=""
            @if ($isGlobalCommission)
                name="commission_value"
            @endif
            x-bind:name="commissionType === '' ? 'commission_value' : null"
        />
        <p
            class="mt-1 text-sm text-slate-500"
            x-text="commissionType === ''
                ? 'Nilai default produk dari pengaturan global ditampilkan otomatis dan tidak bisa diedit di sini.'
                : 'Masukkan nilai custom persen atau rupiah untuk override komisi produk ini.'"
        >{{ $isGlobalCommission ? 'Nilai default produk dari pengaturan global ditampilkan otomatis dan tidak bisa diedit di sini.' : 'Masukkan nilai custom persen atau rupiah untuk override komisi produk ini.' }}</p>
        <x-input-error :messages="$errors->get('commission_value')" class="mt-2" />
    </div>
</div>

@include('partials.crud.form-actions', [
    'submitLabel' => $submitLabel,
    'cancelUrl' => route('products.index'),
])
