@php
    $expenseDateLabel = $expense->expense_date?->locale('id')->translatedFormat('d F Y') ?? '-';
    $categoryLabel = \Illuminate\Support\Str::of((string) $expense->category)->title()->value();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Edit Pengeluaran') }}</h2>
    </x-slot>

    <div class="space-y-5">
        <section class="admin-card p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#934C2D]">Expense</p>
                    <div>
                        <h3 class="text-xl font-semibold text-slate-900 sm:text-2xl">Perbarui pengeluaran yang sudah tercatat</h3>
                        <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                            Koreksi tanggal, kategori, nominal, atau catatan kalau ada yang perlu dirapikan sebelum dipakai sebagai acuan laporan.
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        <span class="font-semibold text-slate-900">{{ format_rupiah($expense->amount) }}</span>
                        <span class="mx-2 text-slate-300">·</span>
                        <span>{{ $expenseDateLabel }}</span>
                        <span class="mx-2 text-slate-300">·</span>
                        <span>{{ $categoryLabel }}</span>
                    </div>
                </div>

                <div class="flex w-full lg:w-auto lg:justify-end">
                    <a href="{{ route('expenses.index') }}" class="btn-neutral-warm w-full justify-center lg:w-auto">
                        Kembali ke Daftar
                    </a>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('expenses.update', $expense) }}" class="space-y-5">
            @csrf
            @method('PUT')

            @include('expenses._form', [
                'expense' => $expense,
                'categories' => $categories,
                'freelanceExpenseDraft' => null,
                'submitLabel' => 'Simpan Perubahan',
            ])
        </form>
    </div>
</x-app-layout>
