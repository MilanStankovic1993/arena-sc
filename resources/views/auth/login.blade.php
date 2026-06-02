<x-guest-layout>
    <div class="space-y-8">
        <div class="space-y-4">
            <span class="eyebrow-chip">Prijava</span>
            <h2 class="section-title text-[2.7rem] sm:text-[3.3rem]">Dobrodosli nazad.</h2>
        </div>

        <x-auth-session-status class="mb-2" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div class="auth-field">
                <x-input-label for="email" value="Email adresa" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="auth-field">
                <div class="flex items-center justify-between gap-3">
                    <x-input-label for="password" value="Lozinka" />
                    @if (Route::has('password.request'))
                        <a class="auth-link text-xs sm:text-sm" href="{{ route('password.request') }}">
                            Zaboravili ste lozinku?
                        </a>
                    @endif
                </div>

                <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <label for="remember_me" class="flex items-center gap-3 rounded-[1.15rem] border border-[rgba(15,42,31,0.12)] bg-white/70 px-4 py-3 text-sm text-[color:var(--arena-muted)]">
                <input id="remember_me" type="checkbox" class="rounded border-[rgba(15,42,31,0.2)] text-[color:var(--arena-forest)] focus:ring-[color:var(--arena-sand)]" name="remember">
                <span>Zapamti me na ovom uredjaju</span>
            </label>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <a class="auth-link" href="{{ route('register') }}">
                    Nemate nalog? Registrujte se
                </a>

                <x-primary-button class="w-full justify-center sm:w-auto">
                    Prijavi se
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
