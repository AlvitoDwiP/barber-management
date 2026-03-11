@php
    $transactionDateValue = old('transaction_date', $transaction?->transaction_date?->format('Y-m-d') ?? now()->toDateString());
    $employeeValue = old('employee_id', $transaction?->employee_id);
    $paymentMethodValue = old('payment_method', $transaction?->payment_method);

    $selectedServiceDefaults = collect($selectedServices ?? [])
        ->map(fn ($id) => (string) $id)
        ->all();

    $oldServices = old('services');
    $selectedServiceIds = is_array($oldServices)
        ? collect($oldServices)->map(fn ($id) => (string) $id)->all()
        : $selectedServiceDefaults;

    $selectedProductDefaults = collect($selectedProducts ?? [])
        ->map(fn ($qty) => $qty === null ? '' : (string) $qty)
        ->all();

    $oldProducts = old('products');
    $productQtyById = is_array($oldProducts)
        ? collect($oldProducts)->map(fn ($qty) => $qty === null ? '' : (string) $qty)->all()
        : $selectedProductDefaults;
@endphp

<div
    class="grid grid-cols-1 gap-6 xl:grid-cols-3"
    x-data="{
        selectedServices: @js(array_values($selectedServiceIds)),
        productQty: @js($productQtyById),
        selectedServiceCount() {
            return this.selectedServices.length;
        },
        selectedProductCount() {
            return Object.values(this.productQty).reduce((count, qty) => count + (Number(qty) > 0 ? 1 : 0), 0);
        }
    }"
>
    <div class="space-y-6 xl:col-span-2">
        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
            <h3 class="text-base font-semibold text-slate-900">Data Transaksi</h3>
            <p class="mt-1 text-sm text-slate-500">Pilih tanggal, pegawai, dan metode pembayaran transaksi.</p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
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
                                {{ $employee->name }}
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
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Pilih Layanan</h3>
                    <p class="text-sm text-slate-500">Pilih satu atau lebih layanan yang diberikan ke pelanggan.</p>
                </div>
                <span class="rounded-full bg-[#FAF3EF] px-3 py-1 text-xs font-medium text-[#7D4026]" x-text="`${selectedServiceCount()} terpilih`"></span>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @forelse ($services as $service)
                    <label class="flex cursor-pointer items-start justify-between gap-3 rounded-lg border border-slate-200 p-3 transition hover:border-[#D9B4A2] hover:bg-[#FAF3EF]">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $service->name }}</p>
                            <p class="text-xs text-slate-500">Rp {{ number_format((float) $service->price, 0, ',', '.') }}</p>
                        </div>

                        <input
                            type="checkbox"
                            name="services[]"
                            value="{{ $service->id }}"
                            class="mt-1 rounded border-slate-300 text-[#934C2D] shadow-sm focus:ring-[#A85F3B]"
                            x-model="selectedServices"
                            @checked(in_array((string) $service->id, $selectedServiceIds, true))
                        />
                    </label>
                @empty
                    <p class="text-sm text-slate-600">Belum ada data layanan.</p>
                @endforelse
            </div>
            <x-input-error :messages="$errors->get('services')" class="mt-2" />
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-slate-900">Produk (Opsional)</h3>
                <p class="text-sm text-slate-500">Produk dengan qty 0 akan diabaikan. Anda wajib mengisi minimal layanan atau produk.</p>
            </div>

            <div class="space-y-3">
                @forelse ($products as $product)
                    <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 p-3 md:grid-cols-5 md:items-end">
                        <div class="md:col-span-2">
                            <p class="text-sm font-medium text-slate-900">{{ $product->name }}</p>
                            <p class="text-xs text-slate-500">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">Stok tersedia</p>
                            <p class="text-sm font-medium text-slate-900">{{ $product->stock }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="products_{{ $product->id }}" :value="__('Qty')" />
                            <x-text-input
                                id="products_{{ $product->id }}"
                                name="products[{{ $product->id }}]"
                                type="number"
                                min="0"
                                step="1"
                                class="mt-1 block w-full"
                                :value="$productQtyById[$product->id] ?? '0'"
                                x-model="productQty['{{ $product->id }}']"
                            />
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">Belum ada data produk.</p>
                @endforelse
            </div>
            <x-input-error :messages="$errors->get('products')" class="mt-2" />
        </section>
    </div>

    <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
        <section class="panel-cream rounded-xl border p-4">
            <h4 class="text-sm font-semibold text-[#7D4026]">Panduan Cepat</h4>
            <ul class="mt-2 space-y-2 text-sm text-[#8B533B]">
                <li>Transaksi bisa layanan saja, produk saja, atau gabungan.</li>
                <li>Isi 0 pada qty untuk mengabaikan produk.</li>
                <li>Pastikan pegawai dan metode pembayaran sudah benar.</li>
            </ul>
        </section>

        <section class="panel-cream rounded-xl border p-4">
            <h4 class="text-sm font-semibold text-[#7D4026]">Ringkasan Input</h4>
            <dl class="mt-3 space-y-2 text-sm text-[#8B533B]">
                <div class="flex items-center justify-between gap-3">
                    <dt>Layanan dipilih</dt>
                    <dd class="font-semibold text-[#6B3721]" x-text="selectedServiceCount()"></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt>Produk qty &gt; 0</dt>
                    <dd class="font-semibold text-[#6B3721]" x-text="selectedProductCount()"></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt>Produk tersedia</dt>
                    <dd class="font-semibold text-[#6B3721]">{{ $products->count() }}</dd>
                </div>
            </dl>
        </section>
    </aside>
</div>

@include('partials.crud.form-actions', [
    'submitLabel' => $submitLabel,
    'cancelUrl' => route('transactions.index'),
])
