@php
    $employeeOptions = $employees->map(fn ($employee) => [
        'id' => $employee->id,
        'name' => $employee->name,
        'is_active' => (bool) $employee->is_active,
        'employment_type' => (string) $employee->employment_type,
        'employment_label' => $employee->employment_type_label,
        'operational_status_label' => $employee->operational_status_label,
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
    $isPayrollOpen = $transaction->payrollPeriod?->status === \App\Models\PayrollPeriod::STATUS_OPEN;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-slate-900">Edit Transaksi</h2>
    </x-slot>

    <div
        class="space-y-6"
        x-data="singleTransactionEditForm({
            employeeOptions: @js($employeeOptions),
            serviceOptions: @js($serviceOptions),
            productOptions: @js($productOptions),
            commissionDefaults: @js($commissionDefaults),
            initialTransaction: @js($initialTransaction),
            errors: @js($errors->getMessages()),
        })"
        x-init="init()"
    >
        <section class="admin-card">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#934C2D]">Koreksi Transaksi</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $transaction->transaction_code }}</h3>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Gunakan halaman ini untuk memperbaiki salah input tanpa menghapus transaksi. Saat disimpan, sistem akan membangun ulang snapshot item transaksi ini, menyesuaikan stok produk, dan menjaga laporan tetap sinkron.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('transactions.show', $transaction) }}" class="btn-neutral-warm">
                        Batal
                    </a>
                </div>
            </div>

            @if ($isPayrollOpen)
                <div class="transaction-alert mt-5">
                    <p class="text-sm font-semibold text-[#7D4026]">Payroll masih open</p>
                    <p class="mt-1 text-sm leading-6 text-[#8B533B]">Transaksi ini masih boleh dikoreksi karena payroll terkait belum ditutup. Setelah payroll berstatus closed, transaksi akan terkunci.</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">
                    <p class="font-semibold">Perubahan belum bisa disimpan.</p>
                    <p class="mt-1">Periksa field yang ditandai. Struktur transaksi tetap dipertahankan sampai seluruh input valid.</p>
                </div>
            @endif
        </section>

        <form method="POST" action="{{ route('transactions.update', $transaction) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="admin-card">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <x-input-label for="transaction_date" :value="__('Tanggal Transaksi')" />
                        <x-text-input
                            id="transaction_date"
                            name="transaction_date"
                            type="text"
                            class="mt-1 block w-full"
                            x-model="transaction.transaction_date"
                            data-flatpickr="date"
                            autocomplete="off"
                            required
                        />
                        <x-input-error :messages="$errors->get('transaction_date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="employee_id" :value="__('Pegawai Transaksi Utama')" />
                        <select id="employee_id" name="employee_id" class="form-brand-control mt-1 block w-full" x-model="transaction.employee_id" required>
                            <option value="">Pilih pegawai transaksi</option>
                            <template x-for="employee in employeeOptions" :key="employee.id">
                                <option :value="String(employee.id)" x-text="`${employee.name} - ${employee.employment_label}${employee.is_active ? '' : ' (Nonaktif)'}`"></option>
                            </template>
                        </select>
                        <p class="mt-2 text-xs leading-5 text-slate-500">Dipakai sebagai pegawai utama transaksi. Jika item melibatkan pegawai berbeda, ubah per baris item di bawah.</p>
                        <x-input-error :messages="$errors->get('employee_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="payment_method" :value="__('Metode Pembayaran')" />
                        <select id="payment_method" name="payment_method" class="form-brand-control mt-1 block w-full" x-model="transaction.payment_method" required>
                            <option value="cash">cash</option>
                            <option value="qr">qr</option>
                        </select>
                        <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                    </div>

                    <div class="rounded-2xl border border-[#E1C5B8] bg-[#FAF3EF] px-4 py-4">
                        <p class="text-sm font-semibold text-[#7D4026]">Aturan koreksi</p>
                        <p class="mt-2 text-sm leading-6 text-[#8B533B]">Harga dan komisi snapshot akan dibangun ulang mengikuti master yang berlaku saat koreksi disimpan. Histori transaksi lain tidak ikut berubah.</p>
                    </div>
                </div>

                <div class="mt-4">
                    <x-input-label for="notes" :value="__('Catatan Transaksi')" />
                    <textarea id="notes" name="notes" rows="3" class="form-brand-control mt-1 block w-full" x-model="transaction.notes"></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </section>

            <section class="admin-card">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Item transaksi</h3>
                        <p class="mt-1 text-sm text-slate-500">Tambah, hapus, ganti produk atau layanan, dan koreksi qty dari transaksi ini melalui daftar item berikut.</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="btn-neutral-warm" @click="applyTransactionEmployeeToItems()">
                            Samakan Pegawai Item
                        </button>
                        <button type="button" class="btn-brand-soft" @click="addItem()">
                            Tambah Item
                        </button>
                    </div>
                </div>

                <x-input-error :messages="$errors->get('items')" class="mt-4" />

                <div class="mt-5 space-y-4">
                    <template x-for="(item, rowIndex) in transaction.items" :key="item.key">
                        <article class="transaction-item-card" :class="itemHasErrors(rowIndex) ? 'ring-1 ring-rose-200' : ''">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900" x-text="`Item ${rowIndex + 1}`"></p>
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide"
                                            :class="item.item_type === 'service' ? 'bg-[#FAF3EF] text-[#7D4026]' : 'bg-slate-100 text-slate-700'"
                                            x-text="item.item_type === 'service' ? 'Layanan' : 'Produk'"
                                        ></span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500" x-text="item.item_type === 'service'
                                        ? 'Qty layanan selalu 1.'
                                        : 'Qty produk bisa dikoreksi dan stok akan disesuaikan otomatis.'"></p>
                                </div>

                                <button type="button" class="btn-neutral-warm self-start" @click="removeItem(rowIndex)">
                                    Hapus
                                </button>
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-12 xl:items-start">
                                <div class="xl:col-span-2">
                                    <x-input-label :value="__('Jenis')" />
                                    <select
                                        class="form-brand-control"
                                        :name="`items[${rowIndex}][item_type]`"
                                        x-model="item.item_type"
                                        @change="changeItemType(rowIndex, $event.target.value)"
                                    >
                                        <option value="service">Layanan</option>
                                        <option value="product">Produk</option>
                                    </select>
                                </div>

                                <div class="xl:col-span-4">
                                    <x-input-label :value="__('Item Master')" />
                                    <template x-if="item.item_type === 'service'">
                                        <select
                                            class="form-brand-control"
                                            :name="`items[${rowIndex}][service_id]`"
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
                                            :name="`items[${rowIndex}][product_id]`"
                                            x-model="item.product_id"
                                        >
                                            <option value="">Pilih produk</option>
                                            <template x-for="product in productOptions" :key="product.id">
                                                <option :value="String(product.id)" x-text="`${product.name} - ${formatCurrency(product.price_minor_units)} (stok ${product.stock})`"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <template x-for="message in item.item_type === 'service' ? fieldErrors(`items.${rowIndex}.service_id`) : fieldErrors(`items.${rowIndex}.product_id`)" :key="message">
                                        <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                                    </template>
                                </div>

                                <div class="xl:col-span-3">
                                    <x-input-label :value="__('Pegawai Item')" />
                                    <select
                                        class="form-brand-control"
                                        :name="`items[${rowIndex}][employee_id]`"
                                        x-model="item.employee_id"
                                        required
                                    >
                                        <option value="">Pilih pegawai item</option>
                                        <template x-for="employee in employeeOptions" :key="employee.id">
                                            <option :value="String(employee.id)" x-text="`${employee.name} - ${employee.employment_label}${employee.is_active ? '' : ' (Nonaktif)'}`"></option>
                                        </template>
                                    </select>
                                    <template x-for="message in fieldErrors(`items.${rowIndex}.employee_id`)" :key="message">
                                        <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                                    </template>
                                </div>

                                <template x-if="item.item_type === 'product'">
                                    <div class="xl:col-span-3">
                                        <x-input-label :value="__('Qty')" />
                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            class="form-brand-control"
                                            :name="`items[${rowIndex}][qty]`"
                                            x-model="item.qty"
                                        />
                                        <template x-for="message in fieldErrors(`items.${rowIndex}.qty`)" :key="message">
                                            <p class="mt-2 text-sm text-rose-600" x-text="message"></p>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <template x-if="item.item_type === 'service'">
                                <input type="hidden" :name="`items[${rowIndex}][qty]`" value="1">
                            </template>

                            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
                                <div class="transaction-preview-card">
                                    <p class="transaction-preview-label">Harga snapshot baru</p>
                                    <p class="transaction-preview-value" x-text="formatCurrency(unitPrice(item))"></p>
                                </div>

                                <div class="transaction-preview-card">
                                    <p class="transaction-preview-label">Sumber komisi</p>
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

                                <div class="transaction-preview-card">
                                    <p class="transaction-preview-label">Info stok</p>
                                    <p class="transaction-preview-value text-sm" x-text="item.item_type === 'product' && selectedOption(item) ? `Sisa master: ${selectedOption(item).stock}` : 'Tidak memakai stok'"></p>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </section>

            <section class="admin-card">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="rounded-2xl border border-[#E1C5B8] bg-[#FAF3EF] px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#8B533B]">Jumlah item</p>
                        <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="selectedItemCount()"></p>
                    </div>
                    <div class="rounded-2xl border border-[#E1C5B8] bg-[#FAF3EF] px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#8B533B]">Total layanan</p>
                        <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatCurrency(serviceSubtotal())"></p>
                    </div>
                    <div class="rounded-2xl border border-[#E1C5B8] bg-[#FAF3EF] px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#8B533B]">Total produk</p>
                        <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatCurrency(productSubtotal())"></p>
                    </div>
                    <div class="rounded-2xl border border-[#E1C5B8] bg-[#FAF3EF] px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#8B533B]">Total transaksi</p>
                        <p class="mt-1 text-sm font-semibold text-[#6B3721]" x-text="formatCurrency(grandTotal())"></p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('transactions.show', $transaction) }}" class="btn-neutral-warm justify-center">
                        Batal
                    </a>

                    <button type="submit" class="btn-brand-primary justify-center">
                        Simpan Perbaikan
                    </button>
                </div>
            </section>
        </form>
    </div>
</x-app-layout>
