<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0F2A1F">

        <title>{{ config('app.name', 'Sportski centar Arena') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
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

            <header class="premium-header">
                <div class="premium-nav-shell">
                    <div class="site-grid premium-nav-layout">
                        <a href="{{ route('home') }}" class="premium-brand">
                            <img src="{{ asset('brand/arena-sc-mark.svg') }}" alt="Sportski centar Arena logo" class="brand-logo brand-logo--header">
                        </a>

                        <nav class="premium-nav-links premium-nav-links--center">
                            <a href="{{ route('home') }}" class="account-nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}">Pocetna</a>
                            <a href="{{ route('dashboard') }}" class="account-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Moj nalog</a>
                            <a href="{{ route('profile.edit') }}" class="account-nav-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">Profil</a>
                            <a href="{{ route('sports.index') }}" class="account-nav-link {{ request()->routeIs('sports.*') || request()->routeIs('courts.*') ? 'is-active' : '' }}">Tereni</a>
                            <a href="{{ route('equipment.index') }}" class="account-nav-link {{ request()->routeIs('equipment.*') ? 'is-active' : '' }}">Oprema</a>
                            <a href="{{ route('price-list.index') }}" class="account-nav-link {{ request()->routeIs('price-list.*') ? 'is-active' : '' }}">Cenovnik</a>
                            <a href="{{ route('events.index') }}" class="account-nav-link {{ request()->routeIs('events.*') ? 'is-active' : '' }}">Dogadjaji</a>
                        </nav>

                        <div class="premium-nav-actions">
                            @if(auth()->user()?->canAccessPanel(app(\Filament\PanelRegistry::class)->get('admin')))
                                <a href="{{ url('/admin') }}" class="arena-button-secondary hidden xl:inline-flex">Admin panel</a>
                            @endif

                            <details class="relative xl:hidden">
                                <summary class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full border border-[color:var(--arena-sand-glow)] bg-[rgba(245,245,242,0.08)] text-[color:var(--arena-sand)] marker:content-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                                    </svg>
                                </summary>
                                <div class="mobile-sheet absolute right-0 top-[calc(100%+0.9rem)] z-20 w-[min(18rem,86vw)]">
                                    <div class="grid gap-3">
                                        <a href="{{ route('home') }}" class="site-link">Pocetna</a>
                                        <a href="{{ route('dashboard') }}" class="site-link">Moj nalog</a>
                                        <a href="{{ route('profile.edit') }}" class="site-link">Profil</a>
                                        <a href="{{ route('sports.index') }}" class="site-link">Tereni</a>
                                        <a href="{{ route('equipment.index') }}" class="site-link">Oprema</a>
                                        <a href="{{ route('price-list.index') }}" class="site-link">Cenovnik</a>
                                        <a href="{{ route('events.index') }}" class="site-link">Dogadjaji</a>
                                        @if(auth()->user()?->canAccessPanel(app(\Filament\PanelRegistry::class)->get('admin')))
                                            <a href="{{ url('/admin') }}" class="site-link">Admin panel</a>
                                        @endif
                                    </div>

                                    <div class="mt-5 grid gap-3">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="arena-button-primary w-full">Odjava</button>
                                        </form>
                                    </div>
                                </div>
                            </details>

                            <div class="hidden xl:block">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="arena-button-primary">Odjava</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="account-grid">
                <div class="account-stack">
                    @if (session('status'))
                        <div class="site-success-banner">
                            {{ session('status') }}
                        </div>
                    @endif

                    @isset($header)
                        <div class="page-hero overflow-hidden">
                            {{ $header }}
                        </div>
                    @endisset

                    {{ $slot }}
                </div>
            </main>

            <footer class="site-grid pb-12 pt-12 sm:pt-14">
                <div class="premium-footer-shell">
                    <div class="site-footer-grid">
                        <div>
                            <span class="footer-kicker">Moj nalog</span>
                            <img src="{{ asset('brand/arena-sc-mark.svg') }}" alt="Sportski centar Arena logo" class="brand-logo brand-logo--footer mt-5">
                            <h2 class="mt-6 max-w-3xl text-3xl text-white sm:text-4xl">NALOG, REZERVACIJE I SADRZAJI U ISTOM PREMIUM SISTEMU.</h2>
                        </div>

                        <div class="footer-links-grid">
                            <a href="{{ route('dashboard') }}">Moj nalog</a>
                            <a href="{{ route('profile.edit') }}">Profil</a>
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
