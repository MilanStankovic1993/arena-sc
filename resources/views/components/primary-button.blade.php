<button {{ $attributes->merge(['type' => 'submit', 'class' => 'auth-button']) }}>
    {{ $slot }}
</button>
