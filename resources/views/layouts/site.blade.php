<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Arena SC' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[var(--arena-cream)] text-slate-900">
        <div class="relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 -z-10 h-[32rem] bg-[radial-gradient(circle_at_top_left,rgba(13,59,102,0.28),transparent_38%),radial-gradient(circle_at_top_right,rgba(215,38,61,0.22),transparent_35%),linear-gradient(180deg,#f7f4ef,rgba(247,244,239,0.8))]"></div>

            <header class="mx-auto flex max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[var(--arena-blue)] text-xl font-black text-white">SC</span>
                    <div>
                        <p class="font-black uppercase tracking-[0.28em] text-slate-900">Arena</p>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Padel · 3x3 · Dogadjaji</p>
                    </div>
                </a>

                <nav class="hidden items-center gap-6 md:flex">
                    <a href="{{ route('home') }}" class="site-link">Home</a>
                    <a href="{{ route('about') }}" class="site-link">O nama</a>
                    <a href="{{ route('sports.index') }}" class="site-link">Tereni</a>
                    <a href="{{ route('equipment.index') }}" class="site-link">Oprema</a>
                    <a href="{{ route('events.index') }}" class="site-link">Dogadjaji</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="arena-button-primary">Moj nalog</a>
                    @else
                        <a href="{{ route('login') }}" class="site-link">Prijava</a>
                        <a href="{{ route('register') }}" class="arena-button-primary">Registracija</a>
                    @endauth
                </nav>
            </header>

            <main>
                @yield('content')
            </main>

            <footer class="mx-auto mt-16 max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
                <div class="site-card overflow-hidden bg-[linear-gradient(135deg,rgba(13,59,102,0.98),rgba(13,59,102,0.85)_60%,rgba(215,38,61,0.88))] p-8 text-white">
                    <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.32em] text-blue-100">Arena SC</p>
                            <h2 class="mt-4 max-w-2xl text-3xl font-black">Rezervacije, oprema, turniri i liga na jednom mestu.</h2>
                            <p class="mt-4 max-w-2xl text-sm leading-7 text-blue-50/80">
                                Ovaj sadržaj je početna verzija sajta. Tekstove, video materijale i detaljne informacije možemo dalje zajedno prilagoditi tvom prostoru i programu.
                            </p>
                        </div>
                        <div class="grid gap-3 text-sm font-semibold uppercase tracking-[0.18em] text-white/80">
                            <a href="{{ route('sports.index') }}">Tereni</a>
                            <a href="{{ route('equipment.index') }}">Oprema</a>
                            <a href="{{ route('events.index') }}">Dogadjaji</a>
                            <a href="{{ route('about') }}">O nama</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
