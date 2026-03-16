<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah Pengeluaran') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('expenses.store') }}" class="space-y-6">
                        @csrf

                        @include('expenses._form', [
                            'expense' => null,
                            'categories' => $categories,
                            'freelanceExpenseDraft' => $freelanceExpenseDraft ?? null,
                            'submitLabel' => 'Simpan',
                        ])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
