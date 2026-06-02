<x-guest-layout>
    <div class="space-y-8">
        <div class="space-y-4">
            <span class="eyebrow-chip">Registracija</span>
            <h2 class="section-title text-[2.7rem] sm:text-[3.3rem]">Kreirajte svoj nalog.</h2>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <div class="auth-field">
                <x-input-label for="name" value="Ime i prezime" />
                <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="auth-field">
                <x-input-label for="phone" value="Telefon" />
                <x-text-input id="phone" type="text" name="phone" :value="old('phone')" autocomplete="tel" />
                <x-input-error :messages="$errors->get('phone')" />
            </div>

            <div class="auth-field">
                <x-input-label for="email" value="Email adresa" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="auth-field">
                    <x-input-label for="password" value="Lozinka" />
                    <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                <div class="auth-field">
                    <x-input-label for="password_confirmation" value="Potvrda lozinke" />
                    <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" />
                </div>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <a class="auth-link" href="{{ route('login') }}">
                    Vec imate nalog? Prijavite se
                </a>

                <x-primary-button class="w-full justify-center sm:w-auto">
                    Kreiraj nalog
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
