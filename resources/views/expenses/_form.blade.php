@php
    $freelanceExpenseDraft = $freelanceExpenseDraft ?? null;
    $freelancePaymentId = old('freelance_payment_id', $freelanceExpenseDraft['freelance_payment_id'] ?? null);
    $dateValue = old('expense_date', $expense?->expense_date?->format('Y-m-d') ?? ($freelanceExpenseDraft['expense_date'] ?? null));
    $categoryValue = old('category', $expense?->category ?? ($freelanceExpenseDraft['category'] ?? null));
    $amountValue = old('amount', $expense?->amount ?? ($freelanceExpenseDraft['amount'] ?? null));
    $noteValue = old('note', $expense?->note ?? ($freelanceExpenseDraft['note'] ?? null));
@endphp

@if ($errors->any())
    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800">
        <p class="font-semibold">Cek lagi form pengeluaran.</p>
        <p class="mt-1">Masih ada field yang belum lengkap atau formatnya belum sesuai.</p>
    </div>
@endif

@if ($freelanceExpenseDraft !== null)
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-900">
        <p class="font-semibold">Pembayaran freelance siap dicatat sebagai pengeluaran</p>
        <p class="mt-1 leading-6">
            {{ $freelanceExpenseDraft['employee_name'] }} untuk transaksi tanggal {{ $freelanceExpenseDraft['work_date_label'] }}
            sebesar {{ $freelanceExpenseDraft['total_commission_label'] }}.
        </p>
        <p class="mt-2 text-xs font-medium uppercase tracking-wide text-emerald-700">
            Angka ini akan masuk ke Pengeluaran Operasional setelah disimpan.
        </p>
    </div>

    <input type="hidden" name="freelance_payment_id" value="{{ $freelancePaymentId }}">
@endif

<section class="admin-card p-4 sm:p-5">
    <div>
        <h3 class="text-base font-semibold text-slate-900">Input Pengeluaran</h3>
        <p class="mt-1 text-sm text-slate-500">Isi tanggal, kategori, dan nominal lebih dulu. Catatan opsional kalau perlu konteks tambahan.</p>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="expense_date" :value="__('Tanggal Pengeluaran')" />
            <p class="mt-1 text-xs leading-5 text-slate-500">Gunakan tanggal saat uang benar-benar keluar.</p>
            <x-text-input
                id="expense_date"
                name="expense_date"
                type="text"
                class="form-brand-control"
                :value="$dateValue"
                data-flatpickr="date"
                autocomplete="off"
                required
            />
            <x-input-error :messages="$errors->get('expense_date')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="category" :value="__('Kategori')" />
            <p class="mt-1 text-xs leading-5 text-slate-500">Pilih kategori yang paling mudah dikenali saat Anda cek ulang.</p>
            <select
                id="category"
                name="category"
                class="form-brand-control"
                required
            >
                <option value="">Pilih kategori pengeluaran</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected($categoryValue === $category)>
                        {{ \Illuminate\Support\Str::of((string) $category)->title()->value() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('category')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="amount" :value="__('Nominal Pengeluaran')" />
            <p class="mt-1 text-xs leading-5 text-slate-500">Masukkan nominal penuh. Angka ini akan tercatat sebagai Pengeluaran Operasional.</p>
            <x-text-input
                id="amount"
                name="amount"
                type="number"
                step="0.01"
                min="0.01"
                inputmode="decimal"
                class="form-brand-control text-lg font-semibold"
                :value="$amountValue"
                placeholder="50000"
                required
            />
            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="note" :value="__('Catatan')" />
            <p class="mt-1 text-xs leading-5 text-slate-500">Opsional. Misalnya nama vendor, tujuan pembelian, atau keterangan singkat lain.</p>
            <textarea
                id="note"
                name="note"
                rows="4"
                class="form-brand-control"
                placeholder="Contoh: beli kabel clipper dan adaptor"
            >{{ $noteValue }}</textarea>
            <x-input-error :messages="$errors->get('note')" class="mt-2" />
        </div>
    </div>

    <div class="mt-4 rounded-xl border border-[#E6D7CF] bg-[#FCF8F5] px-4 py-3 text-sm leading-6 text-[#7D4026]">
        Pengeluaran yang disimpan di sini akan masuk ke <span class="font-semibold">Pengeluaran Operasional</span>
        dan ikut mengurangi <span class="font-semibold">Laba Operasional</span> di laporan.
    </div>

    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
        <a href="{{ route('expenses.index') }}" class="btn-neutral-warm justify-center">
            Batal
        </a>

        <button type="submit" class="btn-brand-primary justify-center">
            {{ $submitLabel }}
        </button>
    </div>
</section>
