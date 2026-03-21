<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Tambah Pengeluaran') }}</h2>
    </x-slot>

    <div class="space-y-5">
        <section class="admin-card p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#934C2D]">Expense</p>
                    <div>
                        <h3 class="text-xl font-semibold text-slate-900 sm:text-2xl">Catat pengeluaran baru</h3>
                        <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                            Simpan pengeluaran harian di sini supaya ringkasan usaha tetap rapi dan angka laporan tidak tertinggal.
                        </p>
                    </div>
                </div>

                <div class="flex w-full lg:w-auto lg:justify-end">
                    <a href="{{ route('expenses.index') }}" class="btn-neutral-warm w-full justify-center lg:w-auto">
                        Kembali ke Daftar
                    </a>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('expenses.store') }}" class="space-y-5">
            @csrf

            @include('expenses._form', [
                'expense' => null,
                'categories' => $categories,
                'freelanceExpenseDraft' => $freelanceExpenseDraft ?? null,
                'submitLabel' => 'Simpan Pengeluaran',
            ])
        </form>
    </div>
</x-app-layout>
