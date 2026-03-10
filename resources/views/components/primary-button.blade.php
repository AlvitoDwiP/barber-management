<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-brand-primary']) }}>
    {{ $slot }}
</button>
