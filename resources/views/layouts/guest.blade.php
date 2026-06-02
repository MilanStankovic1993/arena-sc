<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0F2A1F">

        <title>{{ config('app.name', 'Arena SC') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="auth-shell">
            <div class="auth-grid">
                <section class="auth-stage">
                    <div class="flex h-full flex-col justify-between gap-10">
                        <div>
                            <a href="{{ route('home') }}" class="premium-brand inline-flex">
                                <span class="logo-badge">
                                    <img src="{{ asset('brand/arena-sc-mark.svg') }}" alt="Arena SC logo" class="h-14 w-14 sm:h-16 sm:w-16">
                                </span>
                                <span class="premium-brand-copy">
                                    <span class="block text-base font-black uppercase tracking-[0.28em] text-[color:var(--arena-paper)] sm:text-xl sm:tracking-[0.34em]">Sportski centar</span>
                                    <span class="brand-script -mt-1 block text-[2.4rem] sm:text-[3rem]">Arena</span>
                                </span>
                            </a>

                            <div class="mt-14 max-w-xl space-y-6">
                                <span class="dark-eyebrow-chip">Premium pristup</span>
                                <h1 class="hero-title-dark text-[3rem] sm:text-[4.5rem]">Jedan identitet za rezervacije, naloge i centar.</h1>
                            </div>
                        </div>

                        <div class="metric-ribbon sm:grid-cols-3">
                            <div class="dark-metric-ribbon-card">
                                <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-white/55">1</p>
                                <p class="mt-2 text-lg font-black text-white">Brz pristup</p>
                            </div>
                            <div class="dark-metric-ribbon-card">
                                <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-white/55">2</p>
                                <p class="mt-2 text-lg font-black text-white">Isti identitet</p>
                            </div>
                            <div class="dark-metric-ribbon-card">
                                <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-white/55">3</p>
                                <p class="mt-2 text-lg font-black text-white">Spreman za mobilni</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="auth-form-card flex items-center">
                    <div class="mx-auto w-full max-w-xl">
                        {{ $slot }}
                    </div>
                </section>
            </div>
        </div>
    </body>
</html>
