@props([
    'action',
    'confirmMessage' => 'Yakin ingin menghapus data ini?',
    'buttonText' => 'Hapus',
])

<form method="POST" action="{{ $action }}" class="inline" onsubmit="return confirm(@js($confirmMessage));">
    @csrf
    @method('DELETE')

    <button
        type="submit"
        {{ $attributes->merge([
            'class' => 'inline-flex items-center rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold uppercase tracking-widest text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2',
        ]) }}
    >
        {{ $buttonText }}
    </button>
</form>
