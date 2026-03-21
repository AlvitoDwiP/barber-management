@props([
    'editUrl',
    'deleteUrl',
    'confirmMessage' => 'Yakin ingin menghapus data ini?',
])

<div class="flex items-center gap-2">
    <a
        href="{{ $editUrl }}"
        class="btn-brand-soft"
    >
        Edit
    </a>

    <x-delete-form
        :action="$deleteUrl"
        button-text="Hapus"
        :confirm-message="$confirmMessage"
    />
</div>
