<section class="space-y-6">
    <header>
        <h2 class="text-2xl font-semibold text-[color:var(--arena-forest)]">
            Lozinka
        </h2>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('put')

        <div class="auth-field">
            <x-input-label for="update_password_current_password" value="Trenutna lozinka" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div class="auth-field">
            <x-input-label for="update_password_password" value="Nova lozinka" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div class="auth-field">
            <x-input-label for="update_password_password_confirmation" value="Potvrda lozinke" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Sacuvaj lozinku</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-[color:var(--arena-muted)]"
                >Sacuvano.</p>
            @endif
        </div>
    </form>
</section>
