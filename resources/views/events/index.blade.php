@extends('layouts.site', ['title' => 'Dogadjaji | Arena SC'])

@section('content')
    <section class="site-grid py-10 sm:py-12">
        <div class="page-stack">
            <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <div class="page-hero-dark">
                    <span class="dark-eyebrow-chip">Dogadjaji</span>
                    <h1 class="hero-title-dark mt-6">Turniri i liga za padel pod jednim krovom.</h1>
                    <p class="hero-copy-dark mt-5 max-w-2xl">
                        Ovde korisnici prate prijave, raspored meceva, rezultate i tabelu, dok admin kroz panel vodi pravila, parove i statistiku.
                    </p>
                </div>

                @if ($featuredEvent)
                    <a href="{{ route('events.show', ['event' => $featuredEvent->slug]) }}" class="premium-card p-8 transition hover:-translate-y-1">
                        @if ($featuredEvent->cover_image_url)
                            <div class="mb-6 overflow-hidden rounded-[1.6rem] border border-white/20 bg-[rgba(15,42,31,0.08)]">
                                <img src="{{ $featuredEvent->cover_image_url }}" alt="{{ $featuredEvent->title }}" class="h-56 w-full object-cover">
                            </div>
                        @endif
                        <span class="eyebrow-chip">Izdvojen dogadjaj</span>
                        <h2 class="section-title mt-5 text-[2.4rem] sm:text-[3rem]">{{ $featuredEvent->title }}</h2>
                        <p class="hero-copy mt-4 text-sm">{{ $featuredEvent->summary }}</p>
                        <div class="info-strip mt-6">
                            <span class="info-chip">{{ $featuredEvent->type->label() }}</span>
                            <span class="info-chip">{{ $featuredEvent->status->label() }}</span>
                        </div>
                    </a>
                @endif
            </div>

            <div class="grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
                @foreach ($events as $event)
                    <a href="{{ route('events.show', ['event' => $event->slug]) }}" class="premium-card p-6 transition hover:-translate-y-1">
                        @if ($event->cover_image_url)
                            <div class="mb-5 overflow-hidden rounded-[1.5rem] border border-white/20 bg-[rgba(15,42,31,0.08)]">
                                <img src="{{ $event->cover_image_url }}" alt="{{ $event->title }}" class="h-48 w-full object-cover">
                            </div>
                        @endif

                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">{{ $event->type->label() }}</p>
                                <h2 class="card-title mt-3">{{ $event->title }}</h2>
                            </div>
                            <span class="info-chip">{{ $event->status->label() }}</span>
                        </div>

                        <p class="mt-4 text-sm leading-7 text-[color:var(--arena-muted)]">{{ $event->summary }}</p>

                        <div class="grid gap-3 mt-6 sm:grid-cols-2">
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
