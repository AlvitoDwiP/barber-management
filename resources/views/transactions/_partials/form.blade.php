@php
    $transactionDateValue = old('transaction_date', $transaction?->transaction_date?->format('Y-m-d') ?? now()->toDateString());
    $employeeValue = old('employee_id', $transaction?->employee_id);
    $paymentMethodValue = old('payment_method', $transaction?->payment_method ?? 'cash');
    $notesValue = old('notes', $transaction?->notes);
    $initialServices = old('services', $selectedServices ?? [['service_id' => '']]);
    $initialProducts = old('products', $selectedProducts ?? [['product_id' => '', 'qty' => 1]]);
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

<div
    class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]"
    x-data="transactionFormEditor({
        serviceOptions: @js($serviceOptions),
        productOptions: @js($productOptions),
        initialServices: @js($initialServices),
        initialProducts: @js($initialProducts),
        errors: @js($errors->getMessages()),
    })"
>
    <div class="space-y-6 xl:col-span-2">
        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
            <h3 class="text-base font-semibold text-slate-900">Data Transaksi</h3>
            <p class="mt-1 text-sm text-slate-500">Isi data dasar transaksi dan item yang terjual pada transaksi ini.</p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <x-input-label for="transaction_date" :value="__('Tanggal Transaksi')" />
                    <x-text-input
                        id="transaction_date"
                        name="transaction_date"
                        type="text"
                        class="mt-1 block w-full"
                        :value="$transactionDateValue"
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
                            <option value="{{ $employee->id }}" @selected((string) $employeeValue === (string) $employee->id)>
                                {{ $employee->name }}{{ $employee->is_active ? '' : ' (Nonaktif)' }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('employee_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="payment_method" :value="__('Metode Pembayaran')" />
                    <select id="payment_method" name="payment_method" class="form-brand-control" required>
                        <option value="">Pilih metode</option>
                        <option value="cash" @selected($paymentMethodValue === 'cash')>cash</option>
                        <option value="qr" @selected($paymentMethodValue === 'qr')>qr</option>
                    </select>
                    <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                </div>
            </div>

            <div class="mt-4">
                <x-input-label for="notes" :value="__('Catatan Transaksi')" />
                <textarea
                    id="notes"
                    name="notes"
                    rows="3"
                    class="form-brand-control"
                >{{ $notesValue }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Layanan</h3>
                    <p class="text-sm text-slate-500">Setiap baris layanan otomatis dihitung sebagai 1 layanan.</p>
                </div>
                <button type="button" class="btn-brand-soft" @click="addRow('services')">Tambah Layanan</button>
            </div>

            <div class="space-y-3">
                <template x-for="(row, index) in services" :key="row.key">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,2fr)_160px_auto] lg:items-end">
                            <div>
                                <x-input-label :value="__('Layanan')" />
                                <select class="form-brand-control" :name="`services[${index}][service_id]`" x-model="row.service_id">
                                    <option value="">Pilih layanan</option>
                                    <template x-for="service in serviceOptions" :key="service.id">
                                        <option :value="String(service.id)" x-text="`${service.name} - ${formatCurrency(service.price_minor_units)}`"></option>
                                    </template>
                                </select>
                                <template x-for="message in fieldErrors(`services.${index}.service_id`)" :key="message">
                                    <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                                </template>
                            </div>

                            <div class="rounded-lg bg-slate-50 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Subtotal</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900" x-text="formatCurrency(lineSubtotal('service', row))"></p>
                            </div>

                            <button type="button" class="btn-neutral-warm justify-center" @click="removeRow('services', index)">Hapus</button>
                        </div>
                    </div>
                </template>
            </div>
            <x-input-error :messages="$errors->get('services')" class="mt-2" />
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
            <div class="mb-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Produk</h3>
                        <p class="text-sm text-slate-500">Tambahkan produk yang terjual pada transaksi ini dan atur qty sesuai kebutuhan.</p>
                    </div>
                    <button type="button" class="btn-brand-soft" @click="addRow('products')">Tambah Produk</button>
                </div>
            </div>

            <div class="space-y-3">
                <template x-for="(row, index) in products" :key="row.key">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,2fr)_120px_160px_auto] lg:items-end">
                            <div>
                                <x-input-label :value="__('Produk')" />
                                <select class="form-brand-control" :name="`products[${index}][product_id]`" x-model="row.product_id">
                                    <option value="">Pilih produk</option>
                                    <template x-for="product in productOptions" :key="product.id">
                                        <option :value="String(product.id)" x-text="`${product.name} - ${formatCurrency(product.price_minor_units)} (stok ${product.stock})`"></option>
                                    </template>
                                </select>
                                <template x-for="message in fieldErrors(`products.${index}.product_id`)" :key="message">
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
                                    :name="`products[${index}][qty]`"
                                    x-model="row.qty"
                                />
                                <template x-for="message in fieldErrors(`products.${index}.qty`)" :key="message">
                                    <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                                </template>
                            </div>

                            <div class="rounded-lg bg-slate-50 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Subtotal</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900" x-text="formatCurrency(lineSubtotal('product', row))"></p>
                                <p class="mt-1 text-xs text-slate-500" x-text="row.product_id ? `Stok tersedia: ${option('product', row.product_id)?.stock ?? 0}` : 'Pilih produk terlebih dulu'"></p>
                            </div>

                            <button type="button" class="btn-neutral-warm justify-center" @click="removeRow('products', index)">Hapus</button>
                        </div>
                    </div>
                </template>
            </div>
            <x-input-error :messages="$errors->get('products')" class="mt-2" />
        </section>
    </div>

    <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
        <section class="panel-cream rounded-xl border p-4">
            <h4 class="text-sm font-semibold text-[#7D4026]">Panduan Cepat</h4>
            <ul class="mt-2 space-y-2 text-sm text-[#8B533B]">
                <li>Transaksi boleh layanan saja, produk saja, atau kombinasi keduanya.</li>
                <li>Layanan selalu dihitung 1 per baris, sedangkan produk tetap memakai qty.</li>
                <li>Catatan transaksi bersifat opsional.</li>
            </ul>
        </section>

        <section class="panel-cream rounded-xl border p-4">
            <h4 class="text-sm font-semibold text-[#7D4026]">Ringkasan Input</h4>
            <dl class="mt-3 space-y-2 text-sm text-[#8B533B]">
                <div class="flex items-center justify-between gap-3">
                    <dt>Subtotal layanan</dt>
                    <dd class="font-semibold text-[#6B3721]" x-text="formatCurrency(serviceSubtotal())"></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt>Subtotal produk</dt>
                    <dd class="font-semibold text-[#6B3721]" x-text="formatCurrency(productSubtotal())"></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt>Total transaksi</dt>
                    <dd class="font-semibold text-[#6B3721]" x-text="formatCurrency(grandTotal())"></dd>
                </div>
            </dl>
        </section>
    </aside>
</div>

@include('partials.crud.form-actions', [
    'submitLabel' => $submitLabel,
    'cancelUrl' => route('transactions.index'),
])
