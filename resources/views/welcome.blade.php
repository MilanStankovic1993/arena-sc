@extends('layouts.site', ['title' => 'Arena SC'])

@section('content')
    <div class="page-stack">
        <section class="site-grid pb-8 pt-8 sm:pb-10 sm:pt-10">
            <div class="page-hero-dark overflow-hidden">
                <div class="hero-grid">
                    <div class="relative z-10">
                        <span class="dark-eyebrow-chip">Premium sportski centar</span>
                        <h1 class="hero-title-dark mt-6 max-w-4xl">
                            Rezervacija, oprema i sportski dogadjaji u jednom elegantnom i brzom iskustvu.
                        </h1>
                        <p class="hero-copy-dark mt-6 max-w-2xl">
                            Arena SC spaja ozbiljan ambijent, precizan planer termina i premium vizuelni identitet. Korisnik lako bira sport, konkretan teren i slobodno vreme, bez konfuzije i bez suvisnih koraka.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('booking.index') }}" class="arena-button-primary">Rezervisi termin</a>
                            <a href="{{ route('sports.index') }}" class="arena-button-secondary border-white/15 bg-white/10 text-white hover:bg-white/14 hover:text-white">Pogledaj terene</a>
                        </div>

                        <div class="metric-ribbon mt-8">
                            <div class="dark-metric-ribbon-card">
                                <p class="text-[11px] font-extrabold uppercase tracking-[0.3em] text-white/55">Tereni</p>
                                <p class="mt-3 text-3xl font-black text-[color:var(--arena-sand)]">{{ $sports->sum('courts_count') }}</p>
                            </div>
                            <div class="dark-metric-ribbon-card">
                                <p class="text-[11px] font-extrabold uppercase tracking-[0.3em] text-white/55">Cenovni blokovi</p>
                                <p class="mt-3 text-3xl font-black text-[color:var(--arena-sand)]">{{ $sports->sum('pricing_rules_count') }}</p>
                            </div>
                            <div class="dark-metric-ribbon-card">
                                <p class="text-[11px] font-extrabold uppercase tracking-[0.3em] text-white/55">Oprema</p>
                                <p class="mt-3 text-3xl font-black text-[color:var(--arena-sand)]">{{ $featuredEquipment->count() }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="display-panel relative min-h-[28rem] overflow-hidden rounded-[2rem] border border-white/10 bg-[radial-gradient(circle_at_top_right,rgba(226,204,170,0.24),transparent_30%),linear-gradient(180deg,rgba(255,255,255,0.12),rgba(4,16,12,0.3))] p-6 sm:p-8">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.12),transparent_46%),linear-gradient(135deg,rgba(7,24,18,0.1),rgba(7,24,18,0.72))] backdrop-blur-[3px]"></div>
                        <div class="relative z-10 flex h-full flex-col justify-between">
                            <div class="flex items-start justify-between gap-4">
                                <span class="dark-eyebrow-chip">Prostor i atmosfera</span>
                                <span class="logo-badge">
                                    <img src="{{ asset('brand/arena-sc-mark.png') }}" alt="Arena SC amblem" class="h-16 w-16 sm:h-20 sm:w-20">
                                </span>
                            </div>

                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-[0.34em] text-white/60">Hero kadar</p>
                                <h2 class="mt-4 max-w-xl text-4xl font-black leading-tight text-white sm:text-5xl">
                                    Ovde dolazi tvoj blurovani video koji odmah vodi u rezervaciju.
                                </h2>
                                <p class="mt-4 max-w-lg text-sm leading-7 text-white/72 sm:text-base">
                                    Dok ne ubacimo pravi video materijal, sekcija vec drzi premium dubinu, tamnu atmosferu i fokus na glavni poziv na akciju.
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="rounded-[1.5rem] border border-white/10 bg-white/8 p-4 backdrop-blur">
                                    <p class="text-[11px] font-extrabold uppercase tracking-[0.26em] text-white/55">Pregled</p>
                                    <p class="mt-3 text-2xl font-black text-[color:var(--arena-sand)]">Po sportu</p>
                                </div>
                                <div class="rounded-[1.5rem] border border-white/10 bg-white/8 p-4 backdrop-blur">
                                    <p class="text-[11px] font-extrabold uppercase tracking-[0.26em] text-white/55">Dostupnost</p>
                                    <p class="mt-3 text-2xl font-black text-[color:var(--arena-sand)]">Po terenu</p>
                                </div>
                                <div class="rounded-[1.5rem] border border-white/10 bg-white/8 p-4 backdrop-blur">
                                    <p class="text-[11px] font-extrabold uppercase tracking-[0.26em] text-white/55">Potvrda</p>
                                    <p class="mt-3 text-2xl font-black text-[color:var(--arena-sand)]">Odmah</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="site-grid">
            <div class="split-surface premium-grid">
                <div class="premium-card">
                    <span class="eyebrow-chip">Kako radi sistem</span>
                    <h2 class="hero-title mt-5 max-w-3xl text-4xl sm:text-5xl">Jedan cist tok od izbora sporta do gotove rezervacije.</h2>
                    <p class="hero-copy mt-5 max-w-2xl">
                        Posetilac bira sport, konkretan teren, datum i trajanje od 1h, 1,5h ili 2h. Sistem prikazuje samo slobodne slotove bas za taj teren, sa cenom iz cenovnika po vremenskom bloku.
                    </p>

                    <div class="premium-grid mt-8 md:grid-cols-3">
                        <div class="premium-card bg-[color:var(--arena-paper)] p-5">
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.28em] text-[color:var(--arena-muted)]">Korak 1</p>
                            <p class="mt-3 text-xl font-black text-[color:var(--arena-forest)]">Izaberi sport i teren</p>
                        </div>
                        <div class="premium-card bg-[color:var(--arena-paper)] p-5">
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.28em] text-[color:var(--arena-muted)]">Korak 2</p>
                            <p class="mt-3 text-xl font-black text-[color:var(--arena-forest)]">Pogledaj slobodne slotove</p>
                        </div>
                        <div class="premium-card bg-[color:var(--arena-paper)] p-5">
                            <p class="text-[11px] font-extrabold uppercase tracking-[0.28em] text-[color:var(--arena-muted)]">Korak 3</p>
                            <p class="mt-3 text-xl font-black text-[color:var(--arena-forest)]">Dodaj opremu i potvrdi</p>
                        </div>
                    </div>
                </div>

                <div class="showcase-panel">
                    <div class="premium-grid md:grid-cols-3">
                        @foreach ($sports as $sport)
                            <div class="premium-card-dark">
                                <p class="text-xs font-extrabold uppercase tracking-[0.32em] text-[color:var(--arena-sand)]">{{ $sport->name }}</p>
                                <h3 class="mt-4 text-2xl font-black text-white">{{ $sport->courts_count }} terena</h3>
                                <p class="mt-3 text-sm leading-7 text-white/70">{{ $sport->short_description }}</p>
                                <div class="mt-5 flex flex-wrap gap-2">
                                    <span class="info-chip-soft">{{ $sport->equipment_count }} artikala</span>
                                    <span class="info-chip-soft">{{ $sport->pricing_rules_count }} blokova</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="site-grid">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <span class="eyebrow-chip">Izdvojeni tereni</span>
                    <h2 class="hero-title mt-5 max-w-3xl text-4xl sm:text-5xl">Prostor koji deluje premium i pre nego sto korisnik zakaze prvi termin.</h2>
                </div>
                <a href="{{ route('sports.index') }}" class="arena-button-secondary">Svi tereni</a>
            </div>

            <div class="premium-grid mt-8 lg:grid-cols-3">
                @foreach ($featuredCourts as $court)
                    <a href="{{ route('courts.show', ['court' => $court->slug]) }}" class="premium-card group overflow-hidden p-6 transition hover:-translate-y-1.5">
                        <div class="display-panel rounded-[1.8rem] border border-white/0 bg-[linear-gradient(145deg,rgba(8,38,28,0.98),rgba(18,63,48,0.95))] p-6 text-white">
                            <p class="text-xs font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-sand)]">{{ $court->sport->name }}</p>
                            <h3 class="mt-4 text-3xl font-black">{{ $court->name }}</h3>
                            <p class="mt-4 text-sm leading-7 text-white/70">{{ $court->description }}</p>
                            <div class="info-strip mt-6">
                                <span class="info-chip-soft">{{ $court->location }}</span>
                                <span class="info-chip-soft">{{ $court->surface }}</span>
                            </div>
                        </div>
                        <div class="mt-5 flex items-center justify-between">
                            <span class="text-sm font-extrabold uppercase tracking-[0.18em] text-[color:var(--arena-forest)]">Otvori detalj</span>
                            <span class="text-sm font-extrabold uppercase tracking-[0.18em] text-[color:var(--arena-forest-glow)] transition group-hover:translate-x-1">Pogledaj</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="site-grid pb-10 sm:pb-14">
            <div class="page-hero-dark overflow-hidden">
                <div class="grid gap-8 xl:grid-cols-[0.95fr_1.05fr] xl:items-start">
                    <div>
                        <span class="dark-eyebrow-chip">Oprema i dogadjaji</span>
                        <h2 class="hero-title-dark mt-6 max-w-3xl text-4xl sm:text-5xl">Prodaja, iznajmljivanje, turniri i liga u istom ozbiljnom tonu.</h2>
                        <p class="hero-copy-dark mt-5 max-w-2xl">
                            Sajt ne sluzi samo za rezervaciju. Ovde gradimo kompletno iskustvo centra: oprema koja prati termin i dogadjaji koji drze zajednicu aktivnom.
                        </p>
                        <div class="mt-7 flex flex-wrap gap-3">
                            <a href="{{ route('equipment.index') }}" class="arena-button-primary">Otvori opremu</a>
                            <a href="{{ route('events.index') }}" class="arena-button-secondary border-white/15 bg-white/10 text-white hover:bg-white/14 hover:text-white">Pogledaj dogadjaje</a>
                        </div>
                    </div>

                    <div class="premium-grid lg:grid-cols-2">
                        <div class="premium-grid">
                            @foreach ($featuredEquipment as $item)
                                <div class="premium-card-dark">
                                    <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-white/54">{{ $item->sport?->name ?? 'Oprema' }}</p>
                                    <h3 class="mt-3 text-2xl font-black text-white">{{ $item->name }}</h3>
                                    <p class="mt-3 text-sm leading-7 text-white/70">{{ $item->short_description }}</p>
                                    <div class="mt-5 flex items-center justify-between text-sm font-extrabold">
                                        <span>Iznajmljivanje</span>
                                        <span class="text-[color:var(--arena-sand)]">{{ number_format($item->rental_price, 0, ',', '.') }} RSD</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="premium-grid">
                            @forelse ($featuredEvents as $event)
                                <a href="{{ route('events.show', ['event' => $event->slug]) }}" class="premium-card-dark transition hover:bg-white/12">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-white/55">{{ $event->type->label() }}</p>
                                            <h3 class="mt-2 text-2xl font-black text-white">{{ $event->title }}</h3>
                                        </div>
                                        <span class="info-chip-soft">{{ $event->status->label() }}</span>
                                    </div>
                                    <p class="mt-4 text-sm leading-7 text-white/70">{{ $event->summary }}</p>
                                </a>
                            @empty
                                <div class="premium-card-dark">
                                    <p class="text-sm leading-7 text-white/72">Prvi turniri i liga bice prikazani ovde cim ih uneses kroz admin panel.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
