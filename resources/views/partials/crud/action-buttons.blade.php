@props([
    'editUrl',
    'deleteUrl',
    'confirmMessage' => 'Yakin ingin menghapus data ini?',
])

<div class="flex items-center gap-2">
    <a
        href="{{ $editUrl }}"
        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-100"
    >
        Edit
    </a>

    <x-delete-form
        :action="$deleteUrl"
        button-text="Hapus"
        :confirm-message="$confirmMessage"
    />
</div>
