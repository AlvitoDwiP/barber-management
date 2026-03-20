@php
    $initialEntries = old('entries', [[
        'employee_id' => '',
        'notes' => '',
        'payment_method' => 'cash',
        'items' => [[
            'item_type' => 'service',
            'service_id' => '',
            'product_id' => '',
            'qty' => 1,
        ]],
    ]]);
    $employeeOptions = $employees->map(fn ($employee) => [
        'id' => $employee->id,
        'name' => $employee->name,
        'is_active' => (bool) $employee->is_active,
        'employment_type' => (string) $employee->employment_type,
        'employment_label' => $employee->employment_type_label,
    ])->values();
    $serviceOptions = $services->map(fn ($service) => [
        'id' => $service->id,
        'name' => $service->name,
        'price' => (string) $service->price,
        'commission_type' => $service->commission_type,
        'commission_value' => $service->commission_value === null ? null : (string) $service->commission_value,
    ])->values();
    $productOptions = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'price' => (string) $product->price,
        'stock' => (int) $product->stock,
        'commission_type' => $product->commission_type,
        'commission_value' => $product->commission_value === null ? null : (string) $product->commission_value,
    ])->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">{{ __('Input Harian Transaksi') }}</h2>
    </x-slot>

    <div
        class="space-y-6"
        x-data="dailyBatchTransactionForm({
            employeeOptions: @js($employeeOptions),
            serviceOptions: @js($serviceOptions),
            productOptions: @js($productOptions),
            commissionDefaults: @js($commissionDefaults),
            initialEntries: @js($initialEntries),
            errors: @js($errors->getMessages()),
        })"
        x-init="init()"
    >
        <section class="admin-card">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#934C2D]">Transaksi v1</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Input harian beberapa transaksi sekaligus</h3>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Ini adalah satu-satunya alur input transaksi. Setiap blok di bawah akan disimpan sebagai transaksi terpisah agar operasional harian tetap cepat, tetapi histori tetap rapi untuk audit.</p>
                </div>

                <a href="{{ route('transactions.index') }}" class="btn-neutral-warm">
                    Kembali
                </a>
            </div>

            @if ($activePayroll)
                <div class="transaction-alert mt-5">
                    <p class="text-sm font-semibold text-[#7D4026]">Payroll aktif</p>
                    <p class="mt-1 text-sm leading-6 text-[#8B533B]">
                        Periode {{ $activePayroll->start_date?->locale('id')->translatedFormat('d F Y') ?? '-' }}
                        sampai
                        {{ $activePayroll->end_date?->locale('id')->translatedFormat('d F Y') ?? '-' }} sedang terbuka.
                        Setiap blok dari input harian ini tetap direkam sebagai transaksi terpisah di periode payroll yang sedang berjalan.
                    </p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">
                    <p class="font-semibold">Masih ada input yang perlu diperbaiki.</p>
                    <p class="mt-1">Periksa blok transaksi dan item yang ditandai. Error tetap ditempatkan sedekat mungkin dengan field terkait supaya halaman tidak terasa rusak.</p>
                </div>
            @endif
        </section>

        <form id="daily-batch-form" method="POST" action="{{ route('transactions.daily-batch.store') }}" class="space-y-6" x-ref="dailyBatchForm">
            @csrf

            <section class="admin-card">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.9fr)]">
                    <div>
                        <x-input-label for="transaction_date" :value="__('Tanggal Transaksi')" />
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

                    <div class="rounded-2xl border border-[#E1C5B8] bg-[#FAF3EF] px-4 py-4">
                        <p class="text-sm font-semibold text-[#7D4026]">Aturan input harian</p>
                        <p class="mt-2 text-sm leading-6 text-[#8B533B]">Setiap blok transaksi memakai satu pegawai transaksi. Item di dalam blok cukup memilih layanan atau produk, lalu sistem menampilkan snapshot harga dan komisi dari aturan yang berlaku.</p>
                    </div>
                </div>
            </section>

            <div class="space-y-5">
                <template x-for="(entry, entryIndex) in entries" :key="entry.key">
                    <section
                        class="admin-card"
                        :data-entry-key="entry.key"
                        :class="entryHasErrors(entryIndex)
                            ? 'ring-1 ring-rose-200'
                            : entryNeedsAttention(entryIndex)
                                ? 'ring-1 ring-amber-200'
                                : ''"
                    >
                        <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-slate-900" x-text="`Transaksi ${entryIndex + 1}`"></h3>
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide"
                                        :class="entryStatus(entryIndex).badgeClass"
                                        x-text="entryStatus(entryIndex).label"
                                    ></span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">Setiap blok adalah 1 transaksi terpisah. Item di dalamnya tetap memakai aturan layanan, produk, komisi, dan payroll yang sama di seluruh modul transaksi.</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 self-start">
                                <button type="button" class="btn-brand-soft" @click="duplicateTransaction(entryIndex)">
                                    Duplikat
                                </button>
                                <button type="button" class="btn-neutral-warm" @click="removeTransaction(entryIndex)">
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <x-input-label :value="__('Pegawai Transaksi')" />
                                <select
                                    class="form-brand-control"
                                    :name="`entries[${entryIndex}][employee_id]`"
                                    data-entry-employee-select="true"
                                    x-model="entry.employee_id"
                                    required
                                >
                                    <option value="">Pilih pegawai transaksi</option>
                                    <template x-for="employee in employeeOptions" :key="employee.id">
                                        <option :value="String(employee.id)" x-text="`${employee.name} - ${employee.employment_label}`"></option>
                                    </template>
                                </select>
                                <p class="mt-2 text-xs leading-5 text-slate-500">Semua item dalam blok ini otomatis memakai pegawai yang sama.</p>
                                <template x-for="message in fieldErrors(`entries.${entryIndex}.employee_id`)" :key="message">
                                    <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                                </template>
                            </div>

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

                            <div class="lg:col-span-2 xl:col-span-3">
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
                            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="message"></div>
                        </template>

                        <div class="mt-6 border-b border-slate-200 pb-5">
                            <h4 class="text-sm font-semibold text-slate-900">Item transaksi</h4>
                            <p class="mt-1 text-sm text-slate-500">Form item hanya dipakai untuk memilih item, qty produk, dan melihat preview snapshot harga serta komisi sesuai aturan yang sudah ada.</p>
                        </div>

                        <div class="mt-5 space-y-4">
                            <template x-for="(item, rowIndex) in entry.items" :key="item.key">
                                <article
                                    class="transaction-item-card"
                                    :class="itemHasErrors(entryIndex, rowIndex) ? 'ring-1 ring-rose-200' : ''"
                                >
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-semibold text-slate-900" x-text="`Item ${rowIndex + 1}`"></p>
                                                <span
                                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide"
                                                    :class="item.item_type === 'service'
                                                        ? 'bg-[#FAF3EF] text-[#7D4026]'
                                                        : 'bg-slate-100 text-slate-700'"
                                                    x-text="item.item_type === 'service' ? 'Layanan' : 'Produk'"
                                                ></span>
                                            </div>
                                            <p class="mt-1 text-sm text-slate-500" x-text="item.item_type === 'service'
                                                ? 'Qty layanan selalu 1 dan komisi mengikuti aturan layanan atau global.'
                                                : 'Komisi produk mengikuti aturan produk atau global. Qty dapat diubah.'"></p>
                                        </div>

                                        <button type="button" class="btn-neutral-warm self-start" @click="removeItem(entryIndex, rowIndex)">
                                            Hapus
                                        </button>
                                    </div>

                                    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-12 xl:items-start">
                                        <div class="xl:col-span-3">
                                            <x-input-label :value="__('Jenis')" />
                                            <select
                                                class="form-brand-control"
                                                :name="`entries[${entryIndex}][items][${rowIndex}][item_type]`"
                                                x-model="item.item_type"
                                                @change="changeItemType(entryIndex, rowIndex, $event.target.value)"
                                            >
                                                <option value="service">Layanan</option>
                                                <option value="product">Produk</option>
                                            </select>
                                        </div>

                                        <div class="xl:col-span-7">
                                            <x-input-label :value="__('Item Master')" />
                                            <template x-if="item.item_type === 'service'">
                                                <select
                                                    class="form-brand-control"
                                                    :name="`entries[${entryIndex}][items][${rowIndex}][service_id]`"
                                                    :data-entry-item-select="rowIndex === 0 ? 'true' : null"
                                                    x-model="item.service_id"
                                                >
                                                    <option value="">Pilih layanan</option>
                                                    <template x-for="service in serviceOptions" :key="service.id">
                                                        <option :value="String(service.id)" x-text="`${service.name} - ${formatCurrency(service.price_minor_units)}`"></option>
                                                    </template>
                                                </select>
                                            </template>

                                            <template x-if="item.item_type === 'product'">
                                                <select
                                                    class="form-brand-control"
                                                    :name="`entries[${entryIndex}][items][${rowIndex}][product_id]`"
                                                    :data-entry-item-select="rowIndex === 0 ? 'true' : null"
                                                    x-model="item.product_id"
                                                >
                                                    <option value="">Pilih produk</option>
                                                    <template x-for="product in productOptions" :key="product.id">
                                                        <option :value="String(product.id)" x-text="`${product.name} - ${formatCurrency(product.price_minor_units)} (stok ${product.stock})`"></option>
                                                    </template>
                                                </select>
                                            </template>

                                            <template x-for="message in item.item_type === 'service'
                                                ? fieldErrors(`entries.${entryIndex}.items.${rowIndex}.service_id`)
                                                : fieldErrors(`entries.${entryIndex}.items.${rowIndex}.product_id`)" :key="message">
                                                <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                                            </template>
                                        </div>

                                        <template x-if="item.item_type === 'product'">
                                            <div class="xl:col-span-2">
                                                <x-input-label :value="__('Qty')" />
                                                <input
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    class="form-brand-control"
                                                    :name="`entries[${entryIndex}][items][${rowIndex}][qty]`"
                                                    x-model="item.qty"
                                                />
                                                <template x-for="message in fieldErrors(`entries.${entryIndex}.items.${rowIndex}.qty`)" :key="message">
                                                    <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                                                </template>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                                        <template x-if="item.item_type === 'service'">
                                            <input
                                                type="hidden"
                                                :name="`entries[${entryIndex}][items][${rowIndex}][qty]`"
                                                value="1"
                                            />
                                        </template>

                                        <div class="transaction-preview-card transaction-preview-card-feature md:col-span-2 xl:col-span-2">
                                            <p class="transaction-preview-label">Aturan Item</p>
                                            <p class="transaction-preview-note transaction-preview-note-compact" x-text="item.item_type === 'service'
                                                ? 'Qty layanan selalu 1 dan komisi mengikuti aturan layanan atau global.'
                                                : 'Komisi produk mengikuti aturan produk atau global. Qty dapat diubah.'"></p>
                                        </div>

                                        <div class="transaction-preview-card">
                                            <p class="transaction-preview-label">Harga</p>
                                            <p class="transaction-preview-value" x-text="formatCurrency(unitPrice(item))"></p>
                                            <p class="transaction-preview-note transaction-preview-note-compact" x-text="item.item_type === 'product' && selectedOption(item)
                                                ? `Stok: ${selectedOption(item).stock ?? 0}`
                                                : 'Preview harga item master'"></p>
                                        </div>

                                        <div class="transaction-preview-card">
                                            <p class="transaction-preview-label">Sumber</p>
                                            <p class="transaction-preview-value text-sm" x-text="commissionSourceLabel(item)"></p>
                                        </div>

                                        <div class="transaction-preview-card">
                                            <p class="transaction-preview-label">Komisi</p>
                                            <p class="transaction-preview-value" x-text="formatCurrency(commissionAmount(item))"></p>
                                            <p class="transaction-preview-note transaction-preview-note-compact" x-text="commissionTypeDisplay(item)"></p>
                                        </div>

                                        <div class="transaction-preview-card">
                                            <p class="transaction-preview-label">Subtotal</p>
                                            <p class="transaction-preview-value" x-text="formatCurrency(lineSubtotal(item))"></p>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </div>

                        <div class="mt-5 flex">
                            <button type="button" class="btn-brand-soft self-start" @click="addItem(entryIndex)">
                                Tambah Item
                            </button>
                        </div>

                        <div class="mt-6 rounded-2xl border border-[#E1C5B8] bg-[#FAF3EF] px-4 py-4">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[#8B533B]">Jumlah item</p>
                                    <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="entrySelectedItemCount(entry)"></p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[#8B533B]">Total layanan</p>
                                    <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatCurrency(entryServiceSubtotal(entry))"></p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[#8B533B]">Total produk</p>
                                    <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatCurrency(entryProductSubtotal(entry))"></p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[#8B533B]">Total transaksi</p>
                                    <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatCurrency(entryGrandTotal(entry))"></p>
                                </div>
                            </div>
                        </div>
                    </section>
                </template>
            </div>

            <div class="flex">
                <button type="button" class="btn-brand-soft w-full justify-center sm:w-auto" @click="addTransaction()">
                    Tambah Transaksi
                </button>
            </div>

            <section class="admin-card">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <h4 class="text-base font-semibold text-slate-900">Ringkasan Input Harian</h4>
                        <p class="text-sm text-slate-500">Cek total input hari ini sebelum semua blok disimpan. Ringkas, cepat dibaca, dan tetap nyaman di mobile.</p>
                    </div>

                    <div
                        class="inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-wide"
                        :class="batchSummary().attentionEntries > 0
                            ? 'border-amber-200 bg-amber-50 text-amber-800'
                            : 'border-emerald-200 bg-emerald-50 text-emerald-800'"
                    >
                        <span x-text="batchSummary().attentionEntries > 0
                            ? `${formatWholeNumber(batchSummary().attentionEntries)} blok perlu dicek`
                            : 'Semua blok aktif sudah siap dicek'"></span>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Transaksi terisi</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900" x-text="formatWholeNumber(batchSummary().filledEntries)"></dd>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kas Masuk</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900" x-text="formatCurrency(batchSummary().grossCashIn)"></dd>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cash</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900" x-text="formatCurrency(batchSummary().cash)"></dd>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">QR</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900" x-text="formatCurrency(batchSummary().qr)"></dd>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pendapatan Layanan</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900" x-text="formatCurrency(batchSummary().serviceRevenue)"></dd>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pendapatan Produk</dt>
                            <dd class="mt-1 text-base font-semibold text-slate-900" x-text="formatCurrency(batchSummary().productRevenue)"></dd>
                        </div>
                    </dl>

                    <div class="rounded-2xl border border-[#E1C5B8] bg-[#FAF3EF] px-4 py-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#8B533B]">Blok total</p>
                                <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatWholeNumber(batchSummary().totalBlocks)"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#8B533B]">Siap simpan</p>
                                <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatWholeNumber(batchSummary().readyEntries)"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#8B533B]">Perlu dicek</p>
                                <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatWholeNumber(batchSummary().attentionEntries)"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('transactions.index') }}" class="btn-neutral-warm justify-center">
                        Batal
                    </a>

                    <button
                        type="button"
                        class="btn-brand-primary justify-center"
                        x-on:click="$dispatch('open-modal', 'confirm-save-daily-batch')"
                    >
                        Simpan Semua
                    </button>
                </div>
            </section>
        </form>

        <x-modal name="confirm-save-daily-batch" maxWidth="md" focusable>
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-900">Simpan semua transaksi?</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Semua blok transaksi pada halaman ini akan disimpan sekaligus. Jika ada satu blok yang tidak valid, seluruh proses penyimpanan dibatalkan agar data tetap konsisten.</p>

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
