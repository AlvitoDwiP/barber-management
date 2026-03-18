@php
    $initialEntries = old('entries', [[
        'notes' => '',
        'payment_method' => 'cash',
        'services' => [['service_id' => '']],
        'products' => [['product_id' => '', 'qty' => 1]],
    ]]);
    $serviceOptions = $services->map(fn ($service) => [
        'id' => $service->id,
        'name' => $service->name,
        'price' => (string) $service->price,
    ])->values();
    $productOptions = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'price' => (string) $product->price,
        'stock' => (int) $product->stock,
    ])->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Tambah Transaksi') }}</h2>
    </x-slot>

    <div
        class="space-y-6"
        x-data="dailyBatchTransactionForm({
            serviceOptions: @js($serviceOptions),
            productOptions: @js($productOptions),
            initialEntries: @js($initialEntries),
            errors: @js($errors->getMessages()),
        })"
        x-init="init()"
    >
        <section class="admin-card">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Tambah transaksi</h3>
                    <p class="text-sm text-slate-500">Gunakan halaman ini untuk membuat satu atau beberapa transaksi sekaligus pada tanggal dan pegawai yang sama. Setiap blok akan tetap disimpan sebagai transaksi normal yang terpisah.</p>
                </div>
            </div>

            @if ($activePayroll)
                <div class="mt-5 rounded-xl border border-[#E1C5B8] bg-[#FAF3EF] p-4 text-sm text-[#7D4026]">
                    <p class="font-semibold">Payroll aktif</p>
                    <p class="mt-1">
                        Periode: {{ $activePayroll->start_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                        sampai
                        {{ $activePayroll->end_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                    </p>
                    <p class="mt-1">Transaksi yang dibuat tetap akan mengikuti perilaku payroll saat ini karena semua penyimpanan memakai jalur transaksi normal.</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                    <p class="font-semibold">Masih ada input yang perlu diperbaiki.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <form id="daily-batch-form" method="POST" action="{{ route('transactions.daily-batch.store') }}" class="space-y-6" x-ref="dailyBatchForm">
            @csrf

            <section class="admin-card">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="transaction_date" :value="__('Tanggal')" />
                        <x-text-input
                            id="transaction_date"
                            name="transaction_date"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('transaction_date', now()->toDateString())"
                            data-flatpickr="date"
                            autocomplete="off"
                            required
                        />
                        <x-input-error :messages="$errors->get('transaction_date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="employee_id" :value="__('Pegawai')" />
                        <select id="employee_id" name="employee_id" class="form-brand-control" required>
                            <option value="">Pilih pegawai</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('employee_id')" class="mt-2" />
                    </div>
                </div>
            </section>

            <template x-for="(entry, entryIndex) in entries" :key="entry.key">
                <section class="admin-card">
                    <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-semibold text-slate-900" x-text="`Transaksi ${entryIndex + 1}`"></h3>
                            <p class="text-sm text-slate-500">Setiap blok akan menjadi 1 transaksi normal yang berdiri sendiri.</p>
                        </div>

                        <div class="flex justify-end sm:shrink-0">
                            <button type="button" class="btn-neutral-warm btn-danger w-auto flex-none" @click="removeTransaction(entryIndex)">Hapus Transaksi</button>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <x-input-label :value="__('Metode Pembayaran')" />
                            <select class="form-brand-control" :name="`entries[${entryIndex}][payment_method]`" x-model="entry.payment_method" required>
                                <option value="cash">cash</option>
                                <option value="qr">qr</option>
                            </select>
                            <template x-for="message in fieldErrors(`entries.${entryIndex}.payment_method`)" :key="message">
                                <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                            </template>
                        </div>

                        <div class="md:col-span-3">
                            <x-input-label :value="__('Catatan Transaksi')" />
                            <textarea
                                rows="3"
                                class="form-brand-control"
                                :name="`entries[${entryIndex}][notes]`"
                                x-model="entry.notes"
                            ></textarea>
                            <template x-for="message in fieldErrors(`entries.${entryIndex}.notes`)" :key="message">
                                <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                            </template>
                        </div>
                    </div>

                    <template x-for="message in fieldErrors(`entries.${entryIndex}.items`)" :key="message">
                        <p class="mt-4 text-sm font-medium text-rose-600" x-text="message"></p>
                    </template>

                    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Layanan</h4>
                                    <p class="text-sm text-slate-500">Setiap baris layanan otomatis dihitung sebagai 1 layanan.</p>
                                </div>
                                <button type="button" class="btn-brand-soft" @click="addRow(entryIndex, 'services')">Tambah Layanan</button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(row, rowIndex) in entry.services" :key="row.key">
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.8fr)_150px_120px] lg:items-end">
                                            <div class="min-w-0">
                                                <x-input-label :value="__('Layanan')" />
                                                <select class="form-brand-control min-w-0 text-slate-900" :name="`entries[${entryIndex}][services][${rowIndex}][service_id]`" x-model="row.service_id">
                                                    <option value="">Pilih layanan</option>
                                                    <template x-for="service in serviceOptions" :key="service.id">
                                                        <option :value="String(service.id)" x-text="`${service.name} - ${formatCurrency(service.price_minor_units)}`"></option>
                                                    </template>
                                                </select>
                                                <template x-for="message in fieldErrors(`entries.${entryIndex}.services.${rowIndex}.service_id`)" :key="message">
                                                    <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                                                </template>
                                            </div>

                                            <div class="rounded-lg bg-slate-50 px-4 py-3">
                                                <p class="text-xs uppercase tracking-wide text-slate-500">Subtotal</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-900" x-text="formatCurrency(lineSubtotal('service', row))"></p>
                                            </div>

                                            <button type="button" class="btn-neutral-warm w-full justify-center lg:w-auto" @click="removeRow(entryIndex, 'services', rowIndex)">Hapus</button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <template x-for="message in fieldErrors(`entries.${entryIndex}.services`)" :key="message">
                                <p class="mt-3 text-sm text-rose-600" x-text="message"></p>
                            </template>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Produk</h4>
                                    <p class="text-sm text-slate-500">Tambahkan produk yang terjual dan atur qty sesuai kebutuhan.</p>
                                </div>
                                <button type="button" class="btn-brand-soft" @click="addRow(entryIndex, 'products')">Tambah Produk</button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(row, rowIndex) in entry.products" :key="row.key">
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.8fr)_110px_150px_120px] lg:items-end">
                                            <div class="min-w-0">
                                                <x-input-label :value="__('Produk')" />
                                                <select class="form-brand-control min-w-0 text-slate-900" :name="`entries[${entryIndex}][products][${rowIndex}][product_id]`" x-model="row.product_id">
                                                    <option value="">Pilih produk</option>
                                                    <template x-for="product in productOptions" :key="product.id">
                                                        <option :value="String(product.id)" x-text="`${product.name} - ${formatCurrency(product.price_minor_units)} (stok ${product.stock})`"></option>
                                                    </template>
                                                </select>
                                                <template x-for="message in fieldErrors(`entries.${entryIndex}.products.${rowIndex}.product_id`)" :key="message">
                                                    <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                                                </template>
                                            </div>

                                            <div>
                                                <x-input-label :value="__('Qty')" />
                                                <input
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    class="form-brand-control"
                                                    :name="`entries[${entryIndex}][products][${rowIndex}][qty]`"
                                                    x-model="row.qty"
                                                />
                                                <template x-for="message in fieldErrors(`entries.${entryIndex}.products.${rowIndex}.qty`)" :key="message">
                                                    <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                                                </template>
                                            </div>

                                            <div class="rounded-lg bg-slate-50 px-4 py-3">
                                                <p class="text-xs uppercase tracking-wide text-slate-500">Subtotal</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-900" x-text="formatCurrency(lineSubtotal('product', row))"></p>
                                                <p class="mt-1 text-xs text-slate-500" x-text="row.product_id ? `Stok tersedia: ${option('product', row.product_id)?.stock ?? 0}` : 'Pilih produk terlebih dulu'"></p>
                                            </div>

                                            <button type="button" class="btn-neutral-warm w-full justify-center lg:w-auto" @click="removeRow(entryIndex, 'products', rowIndex)">Hapus</button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <template x-for="message in fieldErrors(`entries.${entryIndex}.products`)" :key="message">
                                <p class="mt-3 text-sm text-rose-600" x-text="message"></p>
                            </template>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl border border-[#E1C5B8] bg-[#FAF3EF] p-4">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-[#8B533B]">Subtotal layanan</p>
                                <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatCurrency(entryServiceSubtotal(entry))"></p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-[#8B533B]">Subtotal produk</p>
                                <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatCurrency(entryProductSubtotal(entry))"></p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-[#8B533B]">Total transaksi</p>
                                <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatCurrency(entryGrandTotal(entry))"></p>
                            </div>
                        </div>
                    </div>
                </section>
            </template>

            <div class="flex">
                <button type="button" class="btn-brand-soft w-full justify-center sm:w-auto" @click="addTransaction()">Tambah Transaksi</button>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('transactions.index') }}" class="btn-neutral-warm flex-1 justify-center sm:flex-none">Kembali</a>
                <button
                    type="button"
                    class="btn-brand-primary flex-1 justify-center sm:flex-none"
                    x-on:click="$dispatch('open-modal', 'confirm-save-daily-batch')"
                >
                    Simpan Semua
                </button>
            </div>
        </form>

        <x-modal name="confirm-save-daily-batch" maxWidth="md" focusable>
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-900">Simpan semua transaksi?</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Semua transaksi dalam halaman ini akan disimpan sekaligus. Pastikan data sudah benar. Jika ada satu transaksi yang tidak valid, seluruh penyimpanan akan dibatalkan.
                </p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        class="btn-neutral-warm"
                        x-on:click="$dispatch('close-modal', 'confirm-save-daily-batch')"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn-brand-primary"
                        x-on:click="$dispatch('close-modal', 'confirm-save-daily-batch'); $refs.dailyBatchForm.requestSubmit()"
                    >
                        Ya, simpan
                    </button>
                </div>
            </div>
        </x-modal>
    </div>
</x-app-layout>
