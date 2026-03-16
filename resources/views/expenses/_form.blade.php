@php
    $freelanceExpenseDraft = $freelanceExpenseDraft ?? null;
    $freelancePaymentId = old('freelance_payment_id', $freelanceExpenseDraft['freelance_payment_id'] ?? null);
    $dateValue = old('expense_date', $expense?->expense_date?->format('Y-m-d') ?? ($freelanceExpenseDraft['expense_date'] ?? null));
    $categoryValue = old('category', $expense?->category ?? ($freelanceExpenseDraft['category'] ?? null));
    $amountValue = old('amount', $expense?->amount ?? ($freelanceExpenseDraft['amount'] ?? null));
    $noteValue = old('note', $expense?->note ?? ($freelanceExpenseDraft['note'] ?? null));
@endphp

@if ($freelanceExpenseDraft !== null)
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        <p class="font-semibold">Pembayaran freelance siap diproses</p>
        <p class="mt-1">
            {{ $freelanceExpenseDraft['employee_name'] }} untuk transaksi tanggal {{ $freelanceExpenseDraft['work_date_label'] }}
            sebesar {{ $freelanceExpenseDraft['total_commission_label'] }}.
        </p>
    </div>

    <input type="hidden" name="freelance_payment_id" value="{{ $freelancePaymentId }}">
@endif

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
    <x-input-label for="amount" :value="__('Jumlah Nominal')" />
    <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="$amountValue" required />
    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
</div>

<div>
    <x-input-label for="note" :value="__('Catatan Pengeluaran')" />
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
