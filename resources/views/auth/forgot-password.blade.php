<x-guest-layout>
    <div class="space-y-8">
        <div class="space-y-4">
            <span class="eyebrow-chip">Obnova pristupa</span>
            <h2 class="section-title text-[2.5rem] sm:text-[3.2rem]">Resetujte lozinku.</h2>
        </div>

        <x-auth-session-status class="mb-2" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <div class="auth-field">
                <x-input-label for="email" value="Email adresa" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <a class="auth-link" href="{{ route('login') }}">
                    Nazad na prijavu
                </a>

                <x-primary-button class="w-full justify-center sm:w-auto">
                    Posalji link
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
