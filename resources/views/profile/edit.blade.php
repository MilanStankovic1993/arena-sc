<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="eyebrow-chip">Profil</span>
            <h2 class="section-title mt-4">Podesavanja naloga.</h2>
        </div>
    </x-slot>

    <div class="space-y-6">
            <div class="account-card">
                <div class="max-w-2xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="account-card">
                <div class="max-w-2xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="account-card">
                <div class="max-w-2xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
    </div>
</x-app-layout>
