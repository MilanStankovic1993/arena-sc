<x-guest-layout>
    <div class="space-y-8">
        <div class="space-y-4">
            <span class="eyebrow-chip">Nova lozinka</span>
            <h2 class="section-title text-[2.5rem] sm:text-[3.2rem]">Postavite novu lozinku.</h2>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="auth-field">
                <x-input-label for="email" value="Email adresa" />
                <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="auth-field">
                    <x-input-label for="password" value="Nova lozinka" />
                    <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                <div class="auth-field">
                    <x-input-label for="password_confirmation" value="Potvrda lozinke" />
                    <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" />
                </div>
            </div>

            <div class="flex justify-end">
                <x-primary-button class="w-full justify-center sm:w-auto">
                    Sacuvaj novu lozinku
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
