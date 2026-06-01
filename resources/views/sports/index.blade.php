@extends('layouts.site', ['title' => 'Tereni i sportovi | Arena SC'])

@section('content')
    <section class="site-grid py-10 sm:py-12">
        <div class="page-stack">
            <div class="page-hero">
                <span class="eyebrow-chip">Tereni i sportovi</span>
                <div class="hero-grid mt-6 items-end">
                    <div>
                        <h1 class="hero-title max-w-4xl text-5xl sm:text-6xl">Pregled sportova i svih raspolozivih terena u premium ambijentu.</h1>
                        <p class="hero-copy mt-5 max-w-2xl">
                            Izaberi sport, otvori teren i vidi cenovnik po vremenskim blokovima, pravila rezervacije i direktan ulaz u planer termina.
                        </p>
                    </div>
                    <div class="metric-ribbon">
                        <div class="metric-ribbon-card">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Sportovi</p>
                            <p class="mt-3 text-3xl font-black text-[color:var(--arena-forest)]">{{ $sports->count() }}</p>
                        </div>
                        <div class="metric-ribbon-card">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Tereni</p>
                            <p class="mt-3 text-3xl font-black text-[color:var(--arena-forest)]">{{ $sports->sum(fn ($sport) => $sport->courts->count()) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
            @foreach ($sports as $sport)
                <section class="split-surface">
                    <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">{{ $sport->name }}</p>
                            <h2 class="mt-3 text-3xl font-black text-[color:var(--arena-forest)]">{{ $sport->name }} program</h2>
                            <p class="mt-4 text-sm leading-8 text-[color:var(--arena-muted)]">{{ $sport->description }}</p>
                            <div class="mt-6 flex flex-wrap gap-3">
                                <span class="rounded-full bg-[color:var(--arena-cream)] px-3 py-2 text-xs font-extrabold uppercase tracking-[0.18em] text-[color:var(--arena-forest)]">{{ $sport->courts->count() }} terena</span>
                                <span class="rounded-full bg-[color:var(--arena-sand-soft)] px-3 py-2 text-xs font-extrabold uppercase tracking-[0.18em] text-[color:var(--arena-forest)]">{{ $sport->pricing_rules_count }} cenovnih blokova</span>
                            </div>
                        </div>
                        <div class="premium-grid md:grid-cols-2">
                            @foreach ($sport->courts as $court)
                                <a href="{{ route('courts.show', ['court' => $court->slug]) }}" class="premium-card bg-[color:var(--arena-paper)] p-5 transition hover:-translate-y-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-xl font-black text-[color:var(--arena-forest)]">{{ $court->name }}</h3>
                                            <p class="mt-2 text-sm text-[color:var(--arena-muted)]">{{ $court->location }}</p>
                                        </div>
                                        <span class="info-chip">{{ $court->surface }}</span>
                                    </div>
                                    <p class="mt-4 text-sm leading-7 text-[color:var(--arena-muted)]">{{ $court->description }}</p>
                                    <div class="mt-5 flex items-center justify-between">
                                        <span class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Rezervacija po terenu</span>
                                        <span class="text-sm font-black uppercase tracking-[0.16em] text-[color:var(--arena-forest-glow)]">Otvori detalj</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endforeach
            </div>
        </div>
    </section>
@endsection
