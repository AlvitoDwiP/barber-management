<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pengaturan Komisi') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-slate-900">Default Komisi Global</h3>
                        <p class="mt-1 text-sm text-slate-600">
                            Nilai ini dipakai sebagai fallback saat layanan atau produk tidak memiliki override komisi sendiri.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('settings.commission.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="rounded-lg border border-slate-200 p-4 space-y-4">
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Komisi Layanan Default</h4>
                                <p class="mt-1 text-sm text-slate-600">Layanan default hanya boleh menggunakan tipe percent.</p>
                            </div>

                            <div>
                                <x-input-label for="default_service_commission_type" :value="__('Tipe Komisi')" />
                                <select
                                    id="default_service_commission_type"
                                    name="default_service_commission_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
                                >
                                    <option value="percent" @selected(old('default_service_commission_type', $settings->default_service_commission_type) === 'percent')>Percent</option>
                                </select>
                                <x-input-error :messages="$errors->get('default_service_commission_type')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="default_service_commission_value" :value="__('Nilai Komisi')" />
                                <x-text-input
                                    id="default_service_commission_value"
                                    name="default_service_commission_value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    class="mt-1 block w-full"
                                    :value="old('default_service_commission_value', $settings->default_service_commission_value)"
                                    required
                                />
                                <p class="mt-1 text-sm text-slate-500">Gunakan nilai 0 sampai 100 untuk komisi persen layanan.</p>
                                <x-input-error :messages="$errors->get('default_service_commission_value')" class="mt-2" />
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-4 space-y-4">
                            <div>
                                <h4 class="text-sm font-semibold text-slate-900">Komisi Produk Default</h4>
                                <p class="mt-1 text-sm text-slate-600">Produk default bisa memakai percent atau fixed sesuai kebutuhan owner.</p>
                            </div>

                            <div>
                                <x-input-label for="default_product_commission_type" :value="__('Tipe Komisi')" />
                                <select
                                    id="default_product_commission_type"
                                    name="default_product_commission_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#A85F3B] focus:ring-[#A85F3B]"
                                >
                                    <option value="percent" @selected(old('default_product_commission_type', $settings->default_product_commission_type) === 'percent')>Percent</option>
                                    <option value="fixed" @selected(old('default_product_commission_type', $settings->default_product_commission_type) === 'fixed')>Fixed</option>
                                </select>
                                <x-input-error :messages="$errors->get('default_product_commission_type')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="default_product_commission_value" :value="__('Nilai Komisi')" />
                                <x-text-input
                                    id="default_product_commission_value"
                                    name="default_product_commission_value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full"
                                    :value="old('default_product_commission_value', $settings->default_product_commission_value)"
                                    required
                                />
                                <p class="mt-1 text-sm text-slate-500">Gunakan 0 sampai 100 untuk percent, atau nominal rupiah untuk fixed.</p>
                                <x-input-error :messages="$errors->get('default_product_commission_value')" class="mt-2" />
                            </div>
                        </div>

                        @include('partials.crud.form-actions', [
                            'submitLabel' => 'Simpan Pengaturan',
                            'cancelUrl' => route('dashboard'),
                        ])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
