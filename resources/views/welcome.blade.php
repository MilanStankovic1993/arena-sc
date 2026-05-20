<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Arena SC</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f5f1e8] text-slate-900">
        <div class="relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 -z-10 h-[28rem] bg-[radial-gradient(circle_at_top,#f8c15c,transparent_55%),radial-gradient(circle_at_right,#0f766e,transparent_35%)] opacity-90"></div>

            <header class="mx-auto flex max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="text-lg font-black tracking-[0.25em]">ARENA SC</a>

                <nav class="hidden items-center gap-6 text-sm font-semibold text-slate-700 md:flex">
                    <a href="{{ route('sports.index') }}">Sportovi</a>
                    <a href="{{ route('equipment.index') }}">Oprema</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-full bg-slate-950 px-4 py-2 text-white">Moj nalog</a>
                    @else
                        <a href="{{ route('login') }}">Prijava</a>
                        <a href="{{ route('register') }}" class="rounded-full bg-slate-950 px-4 py-2 text-white">Kreiraj nalog</a>
                    @endauth
                </nav>
            </header>

            <main class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
                <section class="grid gap-10 py-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                    <div>
                        <span class="inline-flex rounded-full border border-slate-300 bg-white/70 px-4 py-2 text-xs font-bold uppercase tracking-[0.3em] text-slate-700">Padel • Basket 3x3 • Oprema</span>
                        <h1 class="mt-6 max-w-3xl text-5xl font-black leading-none tracking-tight sm:text-6xl">
                            Sportski centar sa online rezervacijama koje admin drzi pod punom kontrolom.
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-700">
                            Arena SC spaja sajt za korisnike i mocan admin panel za upravljanje terenima, cenama po terminu, opremom, analizom korisnika i odobravanjem rezervacija.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-4">
                            <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="rounded-full bg-slate-950 px-6 py-3 text-sm font-semibold text-white">Rezervisi termin</a>
                            <a href="{{ route('sports.index') }}" class="rounded-full border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-800">Pogledaj sportove</a>
                        </div>
                    </div>

                    <div class="grid gap-4">
                        @foreach ($featuredCourts as $court)
                            <a href="{{ route('courts.show', $court) }}" class="rounded-[2rem] bg-white/80 p-6 shadow-sm ring-1 ring-slate-200 backdrop-blur transition hover:-translate-y-1">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-amber-600">{{ $court->sport->name }}</p>
                                        <h2 class="mt-2 text-2xl font-black">{{ $court->name }}</h2>
                                        <p class="mt-3 text-sm text-slate-600">{{ $court->description }}</p>
                                    </div>
                                    <div class="rounded-3xl bg-slate-950 px-4 py-5 text-right text-white">
                                        <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Od</p>
                                        <p class="mt-2 text-2xl font-black">{{ number_format($court->base_price, 0, ',', '.') }}</p>
                                        <p class="text-xs text-slate-300">RSD / sat</p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>

                <section class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach ($sports as $sport)
                        <div class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-teal-700">{{ $sport->name }}</p>
                            <p class="mt-4 text-sm leading-7 text-slate-600">{{ $sport->short_description }}</p>
                            <div class="mt-6 flex gap-3 text-sm font-semibold text-slate-700">
                                <span>{{ $sport->courts_count }} terena</span>
                                <span>•</span>
                                <span>{{ $sport->equipment_count }} artikala</span>
                            </div>
                        </div>
                    @endforeach
                </section>

                <section class="mt-12 rounded-[2.5rem] bg-slate-950 p-8 text-white">
                    <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-sm uppercase tracking-[0.3em] text-amber-300">Oprema na sajtu</p>
                            <h2 class="mt-4 text-3xl font-black">Iznajmljivanje i prodaja opreme na istom mestu.</h2>
                        </div>
                        <a href="{{ route('equipment.index') }}" class="rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-950">Sve stavke opreme</a>
                    </div>

                    <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($featuredEquipment as $item)
                            <div class="rounded-[1.75rem] bg-white/10 p-5">
                                <h3 class="text-lg font-bold">{{ $item->name }}</h3>
                                <p class="mt-3 text-sm text-slate-300">{{ $item->short_description }}</p>
                                <div class="mt-5 flex items-center justify-between">
                                    <span class="text-xs uppercase tracking-[0.25em] text-slate-400">Rent</span>
                                    <span class="font-bold">{{ number_format($item->rental_price, 0, ',', '.') }} RSD</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
