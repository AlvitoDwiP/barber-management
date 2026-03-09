@php
    $nameValue = old('name', $product?->name);
    $priceValue = old('price', $product?->price);
    $stockValue = old('stock', $product?->stock);
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

<div>
    <x-input-label for="stock" :value="__('Stok')" />
    <x-text-input id="stock" name="stock" type="number" step="1" min="0" class="mt-1 block w-full" :value="$stockValue" required />
    <x-input-error :messages="$errors->get('stock')" class="mt-2" />
</div>

<div class="flex items-center gap-3 pt-2">
    <x-primary-button>
        {{ $submitLabel }}
    </x-primary-button>

    <a
        href="{{ route('products.index') }}"
        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-100"
    >
        Batal
    </a>
</div>
