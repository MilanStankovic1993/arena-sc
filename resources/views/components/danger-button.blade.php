<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full px-5 py-3 text-xs font-extrabold uppercase tracking-[0.18em] text-white transition duration-200 bg-[linear-gradient(135deg,#9f312f,#7b1d1d)] hover:brightness-110']) }}>
    {{ $slot }}
</button>
