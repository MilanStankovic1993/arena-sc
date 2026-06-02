<section class="space-y-6">
    <header>
        <h2 class="text-2xl font-semibold text-[color:var(--arena-forest)]">
            Podaci naloga
        </h2>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('patch')

        <div class="auth-field">
            <x-input-label for="name" value="Ime i prezime" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div class="auth-field">
            <x-input-label for="email" value="Email adresa" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-[color:var(--arena-muted)]">
                        Email adresa nije potvrdena.

                        <button form="send-verification" class="auth-link ms-2">
                            Posalji novi verifikacioni email
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 auth-status">
                            Novi verifikacioni link je poslat na vasu email adresu.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Sacuvaj izmene</x-primary-button>

            @if (session('status') === 'profile-updated')
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
