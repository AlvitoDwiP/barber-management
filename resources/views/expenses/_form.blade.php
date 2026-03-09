@php
    $dateValue = old('expense_date', $expense?->expense_date?->format('Y-m-d'));
    $categoryValue = old('category', $expense?->category);
    $amountValue = old('amount', $expense?->amount);
    $noteValue = old('note', $expense?->note);
@endphp

<div>
    <x-input-label for="expense_date" :value="__('Tanggal Pengeluaran')" />
    <x-text-input
        id="expense_date"
        name="expense_date"
        type="text"
        class="mt-1 block w-full"
        :value="$dateValue"
        data-flatpickr="date"
        autocomplete="off"
        required
    />
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
    <x-input-label for="amount" :value="__('Jumlah')" />
    <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="$amountValue" required />
    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
</div>

<div>
    <x-input-label for="note" :value="__('Catatan')" />
    <textarea
        id="note"
        name="note"
        rows="4"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
    >{{ $noteValue }}</textarea>
    <x-input-error :messages="$errors->get('note')" class="mt-2" />
</div>

@include('partials.crud.form-actions', [
    'submitLabel' => $submitLabel,
    'cancelUrl' => route('expenses.index'),
])
