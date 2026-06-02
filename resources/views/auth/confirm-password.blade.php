<x-guest-layout>
    <div class="space-y-8">
        <div class="space-y-4">
            <span class="eyebrow-chip">Bezbedna potvrda</span>
            <h2 class="section-title text-[2.5rem] sm:text-[3.2rem]">Potvrdite svoju lozinku.</h2>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf

            <div class="auth-field">
                <x-input-label for="password" value="Lozinka" />
                <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="flex justify-end">
                <x-primary-button class="w-full justify-center sm:w-auto">
                    Potvrdi
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
