@extends('layouts.site', ['title' => 'Dogadjaji | Arena SC'])

@section('content')
    <section class="site-grid py-10 sm:py-12">
        <div class="page-stack">
            <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <div class="page-hero-dark">
                    <span class="dark-eyebrow-chip">Dogadjaji</span>
                    <h1 class="hero-title-dark mt-6 text-5xl sm:text-6xl">Turniri i liga za padel pod jednim krovom.</h1>
                    <p class="hero-copy-dark mt-5 max-w-2xl">
                        Ovde korisnici prate prijave, raspored meceva, rezultate i tabelu, dok admin kroz panel vodi pravila, parove i statistiku.
                    </p>
                </div>

                @if ($featuredEvent)
                    <a href="{{ route('events.show', ['event' => $featuredEvent->slug]) }}" class="premium-card p-8 transition hover:-translate-y-1">
                        <span class="eyebrow-chip">Izdvojen dogadjaj</span>
                        <h2 class="hero-title mt-5 text-3xl sm:text-4xl">{{ $featuredEvent->title }}</h2>
                        <p class="hero-copy mt-4 text-sm">{{ $featuredEvent->summary }}</p>
                        <div class="info-strip mt-6">
                            <span class="info-chip">{{ $featuredEvent->type->label() }}</span>
                            <span class="info-chip-soft">{{ $featuredEvent->status->label() }}</span>
                        </div>
                    </a>
                @endif
            </div>

            <div class="premium-grid lg:grid-cols-2 xl:grid-cols-3">
                @foreach ($events as $event)
                    <a href="{{ route('events.show', ['event' => $event->slug]) }}" class="premium-card p-6 transition hover:-translate-y-1">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">{{ $event->type->label() }}</p>
                                <h2 class="mt-3 text-2xl font-black text-[color:var(--arena-forest)]">{{ $event->title }}</h2>
                            </div>
                            <span class="info-chip">{{ $event->status->label() }}</span>
                        </div>

                        <p class="mt-4 text-sm leading-7 text-[color:var(--arena-muted)]">{{ $event->summary }}</p>

                        <div class="premium-grid mt-6 sm:grid-cols-2">
                            <div class="premium-card bg-[color:var(--arena-paper)] p-4">
                                <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-[color:var(--arena-muted)]">Ucesnici</p>
                                <p class="mt-2 text-xl font-black text-[color:var(--arena-forest)]">{{ $event->entries_count }}</p>
                            </div>
                            <div class="premium-card bg-[color:var(--arena-sand-soft)] p-4">
                                <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-[color:var(--arena-muted)]">Mecevi</p>
                                <p class="mt-2 text-xl font-black text-[color:var(--arena-forest)]">{{ $event->matches_count }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endsection
