<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0F2A1F">

        <title>{{ config('app.name', 'Sportski centar Arena') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Great+Vibes&display=swap">

        @vite('resources/css/app.css')
    </head>
    <body>
        @php
            $contactEmail = config('arena.contact.email');
            $contactPhone = config('arena.contact.phone');
            $contactInstagram = config('arena.contact.instagram');
        @endphp

        <div class="account-shell">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[38rem] bg-[radial-gradient(circle_at_top_left,rgba(245,245,242,0.16),transparent_30%),radial-gradient(circle_at_top_right,rgba(15,42,31,0.2),transparent_28%),linear-gradient(180deg,rgba(9,23,17,0.12),transparent_74%)]"></div>
            <span class="floating-orb floating-orb--sand absolute -left-16 top-24 -z-10 h-52 w-52"></span>
            <span class="floating-orb floating-orb--forest absolute right-0 top-[28rem] -z-10 h-72 w-72"></span>

            @include('layouts.partials.site-header')

            @include('layouts.partials.flash-dialog')

            <main class="account-grid">
                <div class="account-stack">
                    @isset($header)
                        <div class="page-hero overflow-hidden">
                            {{ $header }}
                        </div>
                    @endisset

                    {{ $slot }}
                </div>
            </main>

            @include('layouts.partials.quick-actions')

            <footer class="site-grid pb-12 pt-12 sm:pt-14">
                <div class="premium-footer-shell">
                    <div class="site-footer-grid">
                        <div>
                            <span class="footer-kicker">Moj nalog</span>
                            <img src="{{ asset('brand/arena-sc-mark.webp') }}" alt="Sportski centar Arena logo" width="640" height="360" loading="lazy" decoding="async" class="brand-logo brand-logo--footer mt-5">
                            <h2 class="mt-6 max-w-3xl text-3xl text-white sm:text-4xl">NALOG, REZERVACIJE I SADRZAJI U ISTOM PREMIUM SISTEMU.</h2>
                        </div>

                        <div class="footer-links-grid">
                            <a href="{{ route('dashboard') }}">Moj nalog</a>
                            <a href="{{ route('booking.index') }}">Rezervisi termin</a>
                            <a href="{{ route('sports.index') }}">Tereni</a>
                            <a href="{{ route('equipment.index') }}">Oprema</a>
                            <a href="{{ route('price-list.index') }}">Cenovnik</a>
                            <a href="{{ route('events.index') }}">Dogadjaji</a>
                        </div>
                    </div>

                    <div class="footer-contact-grid">
                        <div class="footer-contact-card">
                            <span class="footer-kicker">Kontakt</span>
                            <div class="footer-contact-list">
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contactPhone) }}">{{ $contactPhone }}</a>
                                <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                                <a href="{{ $contactInstagram }}" target="_blank" rel="noopener noreferrer">Instagram</a>
                            </div>
                        </div>

                        <div class="footer-contact-card footer-contact-card--action">
                            <span class="footer-kicker">Kontaktiraj nas</span>
                            <div class="footer-contact-actions">
                                <a href="{{ route('home') }}#kontaktiraj-nas" class="arena-button-primary">Kontaktiraj nas</a>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
