<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-full border px-5 py-3 text-xs font-extrabold uppercase tracking-[0.18em] transition duration-200 border-[rgba(15,42,31,0.14)] bg-white text-[color:var(--arena-forest)] hover:border-[rgba(15,42,31,0.24)] hover:bg-[rgba(245,245,242,0.96)]']) }}>
    {{ $slot }}
</button>
