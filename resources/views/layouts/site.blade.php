<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#08261c">
        <title>{{ $title ?? 'Arena SC' }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen">
        <div class="site-shell">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[34rem] bg-[radial-gradient(circle_at_top_left,rgba(226,204,170,0.3),transparent_32%),radial-gradient(circle_at_top_right,rgba(18,63,48,0.16),transparent_28%),linear-gradient(180deg,rgba(255,255,255,0.34),transparent_72%)]"></div>
            <span class="floating-orb floating-orb--sand absolute -left-16 top-24 -z-10 h-52 w-52"></span>
            <span class="floating-orb floating-orb--forest absolute right-0 top-[28rem] -z-10 h-72 w-72"></span>

            <header class="premium-header site-grid pt-4 sm:pt-6">
                <div class="premium-nav-shell flex items-center justify-between gap-4">
                    <a href="{{ route('home') }}" class="premium-brand">
                        <span class="logo-badge">
                            <img src="{{ asset('brand/arena-sc-mark.png') }}" alt="Arena SC logo" class="h-11 w-11 sm:h-12 sm:w-12">
                        </span>
                        <span class="premium-brand-copy">
                            <span class="block text-base font-black uppercase tracking-[0.28em] text-[color:var(--arena-forest)] sm:text-lg sm:tracking-[0.34em]">Arena SC</span>
                            <span class="block text-[10px] font-extrabold uppercase tracking-[0.22em] text-[color:var(--arena-muted)] sm:text-[11px]">Padel | 3x3 | Liga | Turniri</span>
                        </span>
                    </a>

                    <nav class="premium-nav-links">
                        <a href="{{ route('home') }}" class="premium-nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}">Pocetna</a>
                        <a href="{{ route('about') }}" class="premium-nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}">O nama</a>
                        <a href="{{ route('sports.index') }}" class="premium-nav-link {{ request()->routeIs('sports.*') || request()->routeIs('courts.*') ? 'is-active' : '' }}">Tereni</a>
                        <a href="{{ route('equipment.index') }}" class="premium-nav-link {{ request()->routeIs('equipment.*') ? 'is-active' : '' }}">Oprema</a>
                        <a href="{{ route('events.index') }}" class="premium-nav-link {{ request()->routeIs('events.*') ? 'is-active' : '' }}">Dogadjaji</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="arena-button-primary">Moj nalog</a>
                        @else
                            <a href="{{ route('login') }}" class="site-link">Prijava</a>
                            <a href="{{ route('register') }}" class="arena-button-primary">Registracija</a>
                        @endauth
                    </nav>

                    <details class="relative xl:hidden">
                        <summary class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full border border-[color:var(--arena-border)] bg-white/80 text-[color:var(--arena-forest)] marker:content-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                        </summary>
                        <div class="mobile-sheet absolute right-0 top-[calc(100%+0.9rem)] z-20 w-[min(18rem,86vw)]">
                            <div class="grid gap-3">
                                <a href="{{ route('home') }}" class="site-link">Pocetna</a>
                                <a href="{{ route('about') }}" class="site-link">O nama</a>
                                <a href="{{ route('sports.index') }}" class="site-link">Tereni</a>
                                <a href="{{ route('equipment.index') }}" class="site-link">Oprema</a>
                                <a href="{{ route('events.index') }}" class="site-link">Dogadjaji</a>
                            </div>

                            <div class="mt-5 grid gap-3">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="arena-button-primary w-full">Moj nalog</a>
                                @else
                                    <a href="{{ route('login') }}" class="arena-button-secondary w-full">Prijava</a>
                                    <a href="{{ route('register') }}" class="arena-button-primary w-full">Registracija</a>
                                @endauth
                            </div>
                        </div>
                    </details>
                </div>
            </header>

            <main>
                @yield('content')
            </main>

            <footer class="site-grid pb-12 pt-16 sm:pt-20">
                <div class="page-hero-dark overflow-hidden">
                    <div class="site-footer-grid">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="logo-badge">
                                    <img src="{{ asset('brand/arena-sc-mark.png') }}" alt="Arena SC logo" class="h-10 w-10">
                                </span>
                                <p class="text-sm font-extrabold uppercase tracking-[0.34em] text-[color:var(--arena-sand)]">Arena SC</p>
                            </div>
                            <h2 class="mt-5 max-w-3xl text-3xl font-black sm:text-4xl">Savremen sportski centar za rezervacije, opremu, turnire i ligu.</h2>
                            <p class="mt-4 max-w-2xl text-sm leading-7 text-white/72">
                                Premium vizuelni sistem, cist korisnicki tok i ozbiljan sportski identitet sada rade kao jedna celina spremna za dalje sirenje centra.
                            </p>
                        </div>

                        <div class="grid gap-3 text-sm font-extrabold uppercase tracking-[0.18em] text-white/78 sm:grid-cols-2">
                            <a href="{{ route('booking.index') }}">Rezervisi termin</a>
                            <a href="{{ route('sports.index') }}">Tereni</a>
                            <a href="{{ route('equipment.index') }}">Oprema</a>
                            <a href="{{ route('events.index') }}">Dogadjaji</a>
                            <a href="{{ route('about') }}">O nama</a>
                            @auth
                                <a href="{{ route('dashboard') }}">Moj nalog</a>
                            @else
                                <a href="{{ route('register') }}">Registracija</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
