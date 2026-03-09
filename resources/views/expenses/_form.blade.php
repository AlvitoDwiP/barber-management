@php
    $dateValue = old('expense_date', $expense?->expense_date?->format('Y-m-d'));
    $categoryValue = old('category', $expense?->category);
    $amountValue = old('amount', $expense?->amount);
    $noteValue = old('note', $expense?->note);
@endphp

<div>
    <x-input-label for="expense_date" :value="__('Tanggal Pengeluaran')" />
    <x-text-input id="expense_date" name="expense_date" type="date" class="mt-1 block w-full" :value="$dateValue" required />
    <x-input-error :messages="$errors->get('expense_date')" class="mt-2" />
</div>

<div>
    <x-input-label for="category" :value="__('Kategori')" />
    <select
        id="category"
        name="category"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        required
    >
        <option value="">Pilih kategori</option>
        @foreach ($categories as $category)
            <option value="{{ $category }}" @selected($categoryValue === $category)>{{ $category }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('category')" class="mt-2" />
</div>

<div>
    <x-input-label for="amount" :value="__('Jumlah Nominal Pengeluaran')" />
    <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="$amountValue" required />
    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
</div>

<div>
    <x-input-label for="note" :value="__('Catatan Pengeluaran (optional)')" />
    <textarea
        id="note"
        name="note"
        rows="4"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
    >{{ $noteValue }}</textarea>
    <x-input-error :messages="$errors->get('note')" class="mt-2" />
</div>

<div class="flex items-center gap-3 pt-2">
    <x-primary-button>
        {{ $submitLabel }}
    </x-primary-button>

    <a
        href="{{ route('expenses.index') }}"
        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-100"
    >
        Batal
    </a>
</div>
