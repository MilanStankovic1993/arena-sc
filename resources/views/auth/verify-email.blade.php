<x-guest-layout>
    <div class="space-y-8">
        <div class="space-y-4">
            <span class="eyebrow-chip">Verifikacija</span>
            <h2 class="section-title text-[2.5rem] sm:text-[3.2rem]">Potvrdite email adresu.</h2>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="auth-status">
                Novi verifikacioni link je poslat na email adresu koju ste uneli prilikom registracije.
            </div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <x-primary-button class="w-full justify-center sm:w-auto">
                    Posalji novi link
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="auth-link">
                    Odjavi se
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
