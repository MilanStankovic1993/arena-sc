@extends('layouts.site', ['title' => 'Arena SC'])

@section('content')
    <section class="mx-auto grid max-w-7xl gap-10 px-4 py-8 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:px-8">
        <div class="py-10">
            <span class="site-pill">Padel · Basket 3x3 · Turniri · Liga</span>
            <h1 class="mt-6 max-w-4xl text-5xl font-black leading-none tracking-tight text-[var(--arena-blue)] sm:text-6xl">
                Rezervacije termina, oprema i sportski dogadjaji u jednom modernom centru.
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                Arena SC je zamišljena kao mesto gde korisnik prvo vidi prostor, lako proveri slobodan termin, odmah zna cenu i tek onda, uz registraciju naloga, završava rezervaciju i po želji dodaje opremu.
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ route('sports.index') }}" class="arena-button-primary">Rezervisi termin</a>
                <a href="{{ route('events.index') }}" class="arena-button-secondary">Pogledaj dogadjaje</a>
            </div>
        </div>

        <div class="grid gap-4">
            <div class="site-card overflow-hidden bg-[linear-gradient(160deg,rgba(13,59,102,0.95),rgba(13,59,102,0.72)_50%,rgba(215,38,61,0.88))] p-6 text-white">
                <div class="grid gap-4 md:grid-cols-[1.3fr_0.7fr]">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.32em] text-blue-100">Hero video zona</p>
                        <h2 class="mt-3 text-3xl font-black">Ovde postavljamo snimke prostora, terena i event atmosfere.</h2>
                        <p class="mt-4 text-sm leading-7 text-blue-50/80">
                            Trenutno je ovo stilizovan placeholder blok za video pozadinu. Kada dostaviš snimke, lako ćemo ih zameniti pravim autoplay loop sekcijama.
                        </p>
                    </div>
                    <div class="rounded-[1.75rem] bg-white/10 p-5 backdrop-blur">
                        <p class="text-xs font-bold uppercase tracking-[0.28em] text-red-100">Live info</p>
                        <div class="mt-5 space-y-4">
                            <div>
                                <p class="text-3xl font-black">{{ $sports->sum('courts_count') }}</p>
                                <p class="text-xs uppercase tracking-[0.22em] text-blue-100">Aktivnih terena</p>
                            </div>
                            <div>
                                <p class="text-3xl font-black">{{ $featuredEquipment->count() }}</p>
                                <p class="text-xs uppercase tracking-[0.22em] text-blue-100">Top artikala opreme</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($featuredCourts as $court)
                    <a href="{{ route('courts.show', ['court' => $court->slug]) }}" class="site-card p-5 transition hover:-translate-y-1">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-[var(--arena-red)]">{{ $court->sport->name }}</p>
                        <h3 class="mt-3 text-2xl font-black text-[var(--arena-blue)]">{{ $court->name }}</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $court->description }}</p>
                        <div class="mt-5 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Od cene</span>
                            <span class="text-lg font-black text-[var(--arena-blue)]">{{ number_format($court->base_price, 0, ',', '.') }} RSD</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto mt-10 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="site-card p-8">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-[var(--arena-blue-soft)]">Kako radi rezervacija</p>
                <h2 class="mt-4 text-3xl font-black text-[var(--arena-blue)]">Transparentan pregled termina pre prijave.</h2>
                <div class="mt-6 space-y-5 text-sm leading-7 text-slate-600">
                    <p>Gost može da pregleda slobodne slotove, trajanje termina i cenu po danu za 1h, 1.5h ili 2h.</p>
                    <p>Kada odluči da rezerviše, sistem ga vodi na registraciju ili prijavu. Tek prijavljen korisnik može da potvrdi termin.</p>
                    <p>U istom koraku korisnik može da doda i opremu za iznajmljivanje, ali to nije obavezno.</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($sports as $sport)
                    <div class="site-card p-6">
                        <p class="text-xs font-bold uppercase tracking-[0.32em] text-[var(--arena-red)]">{{ $sport->name }}</p>
                        <h3 class="mt-3 text-xl font-black text-[var(--arena-blue)]">{{ $sport->courts_count }} terena</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $sport->short_description }}</p>
                        <div class="mt-5 flex items-center gap-3 text-xs font-bold uppercase tracking-[0.24em] text-slate-500">
                            <span>{{ $sport->equipment_count }} artikala</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto mt-12 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="site-card overflow-hidden bg-[linear-gradient(135deg,rgba(215,38,61,0.95),rgba(13,59,102,0.96))] p-8 text-white">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.3em] text-red-100">Oprema i dogadjaji</p>
                    <h2 class="mt-4 text-3xl font-black">Prodaja, iznajmljivanje i kalendar turnira na istom sajtu.</h2>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('equipment.index') }}" class="rounded-full bg-white px-5 py-3 text-sm font-bold uppercase tracking-[0.18em] text-[var(--arena-blue)]">Oprema</a>
                    <a href="{{ route('events.index') }}" class="rounded-full border border-white/40 px-5 py-3 text-sm font-bold uppercase tracking-[0.18em] text-white">Dogadjaji</a>
                </div>
            </div>

            <div class="mt-8 grid gap-4 lg:grid-cols-2">
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($featuredEquipment as $item)
                        <div class="rounded-[1.75rem] bg-white/10 p-5 backdrop-blur">
                            <p class="text-xs font-bold uppercase tracking-[0.28em] text-blue-100">{{ $item->sport?->name ?? 'Oprema' }}</p>
                            <h3 class="mt-3 text-xl font-black">{{ $item->name }}</h3>
                            <p class="mt-3 text-sm leading-7 text-white/75">{{ $item->short_description }}</p>
                            <div class="mt-5 flex items-center justify-between text-sm font-bold">
                                <span>Rent</span>
                                <span>{{ number_format($item->rental_price, 0, ',', '.') }} RSD</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-4">
                    @forelse ($featuredEvents as $event)
                        <a href="{{ route('events.show', ['event' => $event->slug]) }}" class="rounded-[1.75rem] bg-white/10 p-5 backdrop-blur transition hover:bg-white/15">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-red-100">{{ $event->type->label() }}</p>
                                    <h3 class="mt-2 text-2xl font-black">{{ $event->title }}</h3>
                                </div>
                                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em]">{{ $event->status->label() }}</span>
                            </div>
                            <p class="mt-4 text-sm leading-7 text-white/75">{{ $event->summary }}</p>
                        </a>
                    @empty
                        <div class="rounded-[1.75rem] bg-white/10 p-5 backdrop-blur">
                            <p class="text-sm leading-7 text-white/75">Prvi turniri i liga biće prikazani ovde čim ih uneseš kroz admin panel.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
