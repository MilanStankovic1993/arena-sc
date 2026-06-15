@extends('layouts.site', [
    'title' => 'Sportski centar Arena | Padel i Basket 3x3 | Kraljevo',
    'metaDescription' => 'Sportski centar Arena u Kraljevu nudi padel, basket 3x3, rezervacije termina, cenovnik, opremu i sportske dogadjaje na jednom mestu.',
    'metaKeywords' => 'sportski centar, sportski centar kraljevo, sportski centar arena, padel kraljevo, basket 3x3 kraljevo, kraljevo padel, kraljevo basket, arena kraljevo',
    'metaImage' => asset('media/home/hero-exterior.png'),
])

@section('content')
    <div class="page-stack home-page-stack">
        <section class="home-hero" style="background-image: linear-gradient(90deg, rgba(7, 16, 13, 0.74) 0%, rgba(7, 16, 13, 0.42) 44%, rgba(7, 16, 13, 0.72) 100%), url('{{ asset('media/home/hero-exterior.png') }}');">
            <div class="site-grid home-hero__inner">
                <div class="home-hero__content">
                    <span class="dark-eyebrow-chip">Sportski centar Arena</span>
                    <h1 class="home-hero__title">REZERVISI TERMIN</h1>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('booking.index') }}" class="arena-button-primary">Rezervisi termin</a>
                        <a href="#kontaktiraj-nas" class="arena-button-secondary">Kontaktiraj nas</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="site-grid">
            <div class="home-split-feature">
                <div class="home-split-feature__copy">
                    <span class="eyebrow-chip">Sve na jednom mestu</span>
                    <h2 class="section-title mt-5">PADEL, BASKET 3X3 I RESTORAN SA PREKO 100 MESTA.</h2>
                </div>

                <div class="home-split-feature__visual">
                    <img src="{{ asset('media/home/all-in-one.png') }}" alt="Sportski centar Arena - vise sadrzaja na jednom mestu" class="home-section-image">
                </div>
            </div>
        </section>

        <section class="home-courts-cta" style="background-image: linear-gradient(90deg, rgba(6, 12, 10, 0.82) 0%, rgba(6, 12, 10, 0.54) 46%, rgba(6, 12, 10, 0.82) 100%), url('{{ asset('media/home/courts-night.png') }}');">
            <div class="site-grid home-courts-cta__inner">
                <div class="home-courts-cta__content">
                    <span class="dark-eyebrow-chip">Tereni</span>
                    <h2 class="section-title-dark mt-5">PROSTOR ZA PADEL I BASKET 3X3 U JEDNOM PREMIUM CENTRU.</h2>
                    <div class="mt-7">
                        <a href="{{ route('sports.index') }}" class="arena-button-primary">Pogledaj terene</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="kontaktiraj-nas" class="site-grid">
            <div class="home-contact-panel">
                <div class="home-contact-panel__copy">
                    <span class="eyebrow-chip">Kontaktiraj nas</span>
                    <h2 class="section-title mt-5">PITAJ ZA TERMIN, DOGADJAJ ILI SARADNJU.</h2>
                </div>

                <div class="home-contact-panel__form-shell">
                    <form action="{{ route('contact.store') }}" method="POST" class="home-contact-form">
                        @csrf

                        <div class="home-contact-form__grid">
                            <div class="auth-field">
                                <x-input-label for="contact_name" value="Ime i prezime" />
                                <x-text-input id="contact_name" type="text" name="name" :value="old('name')" required />
                                <x-input-error :messages="$errors->get('name')" />
                            </div>

                            <div class="auth-field">
                                <x-input-label for="contact_phone" value="Telefon" />
                                <x-text-input id="contact_phone" type="text" name="phone" :value="old('phone')" />
                                <x-input-error :messages="$errors->get('phone')" />
                            </div>
                        </div>

                        <div class="home-contact-form__grid">
                            <div class="auth-field">
                                <x-input-label for="contact_email" value="Email adresa" />
                                <x-text-input id="contact_email" type="email" name="email" :value="old('email')" required />
                                <x-input-error :messages="$errors->get('email')" />
                            </div>
                        </div>

                        <div class="auth-field">
                            <x-input-label for="contact_message" value="Poruka" />
                            <textarea id="contact_message" name="message" rows="5" class="auth-textarea" required>{{ old('message') }}</textarea>
                            <x-input-error :messages="$errors->get('message')" />
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="arena-button-primary">Posalji poruku</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
