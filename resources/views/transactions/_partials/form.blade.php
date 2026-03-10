@php
    $transactionDateValue = old('transaction_date', $transaction?->transaction_date?->format('Y-m-d'));
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

<div class="space-y-6">
    <div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        Transaksi dapat berisi layanan saja, produk saja, atau kombinasi keduanya.
    </div>

    <div class="rounded-md border border-gray-200 p-4 space-y-4">
        <h3 class="text-base font-semibold text-gray-900">Data Transaksi</h3>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                <select
                    id="employee_id"
                    name="employee_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
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
                <select
                    id="payment_method"
                    name="payment_method"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="">Pilih metode</option>
                    <option value="cash" @selected($paymentMethodValue === 'cash')>cash</option>
                    <option value="qr" @selected($paymentMethodValue === 'qr')>qr</option>
                </select>
                <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="rounded-md border border-gray-200 p-4 space-y-4">
        <h3 class="text-base font-semibold text-gray-900">Layanan</h3>

        <div class="space-y-3">
            @forelse ($services as $service)
                <label class="flex items-center justify-between rounded-md border border-gray-200 px-3 py-2">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $service->name }}</p>
                        <p class="text-xs text-gray-600">Rp {{ number_format((float) $service->price, 0, ',', '.') }}</p>
                    </div>

                    <input
                        type="checkbox"
                        name="services[]"
                        value="{{ $service->id }}"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        @checked(in_array((string) $service->id, $selectedServiceIds, true))
                    />
                </label>
            @empty
                <p class="text-sm text-gray-600">Belum ada data layanan.</p>
            @endforelse
        </div>
        <x-input-error :messages="$errors->get('services')" class="mt-2" />
    </div>

    <div class="rounded-md border border-gray-200 p-4 space-y-4">
        <h3 class="text-base font-semibold text-gray-900">Produk (Opsional)</h3>
        <p class="text-sm text-gray-600">
            Produk dengan qty 0 akan diabaikan. Isi minimal satu layanan atau satu produk.
        </p>

        <div class="space-y-3">
            @forelse ($products as $product)
                <div class="grid grid-cols-1 gap-3 rounded-md border border-gray-200 px-3 py-2 md:grid-cols-4 md:items-end">
                    <div class="md:col-span-2">
                        <p class="text-sm font-medium text-gray-900">{{ $product->name }}</p>
                        <p class="text-xs text-gray-600">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-600">Stok</p>
                        <p class="text-sm text-gray-900">{{ $product->stock }}</p>
                    </div>

                    <div>
                        <x-input-label for="products_{{ $product->id }}" :value="__('Qty')" />
                        <x-text-input
                            id="products_{{ $product->id }}"
                            name="products[{{ $product->id }}]"
                            type="number"
                            min="0"
                            step="1"
                            class="mt-1 block w-full"
                            :value="$productQtyById[$product->id] ?? '0'"
                        />
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-600">Belum ada data produk.</p>
            @endforelse
        </div>
        <x-input-error :messages="$errors->get('products')" class="mt-2" />
    </div>
</div>

@include('partials.crud.form-actions', [
    'submitLabel' => $submitLabel,
    'cancelUrl' => route('transactions.index'),
])
