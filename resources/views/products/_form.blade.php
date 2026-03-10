@php
    $nameValue = old('name', $product?->name);
    $priceValue = old('price', $product?->price);
    $stockValue = old('stock', $product?->stock);
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

@include('partials.crud.form-actions', [
    'submitLabel' => $submitLabel,
    'cancelUrl' => route('products.index'),
])
