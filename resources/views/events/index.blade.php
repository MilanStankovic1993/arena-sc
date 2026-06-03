@extends('layouts.site', ['title' => 'Dogadjaji | Arena SC'])

@section('content')
    <div class="page-stack events-page-stack">
        <section
            class="events-hero"
            style="background-image: linear-gradient(90deg, rgba(7, 16, 13, 0.86) 0%, rgba(7, 16, 13, 0.42) 44%, rgba(7, 16, 13, 0.82) 100%), url('{{ asset('media/home/events-hero.png') }}');"
        >
            <div class="site-grid events-hero__inner">
                <div class="events-hero__content">
                    <span class="dark-eyebrow-chip">Dogadjaji</span>
                    <h1 class="hero-title-dark max-w-5xl">TURNIRI, LIGE I SPORTSKE PRICE NA JEDNOM MESTU.</h1>
                    <div class="events-hero__chips">
                        <span class="info-chip-soft-dark">Padel</span>
                        <span class="info-chip-soft-dark">Basket 3x3</span>
                        <span class="info-chip-soft-dark">Rezultati i tabela</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="site-grid pb-10 sm:pb-14">
            <div class="events-section-stack">
                @if ($featuredEvent)
                    <a href="{{ route('events.show', ['event' => $featuredEvent->slug]) }}" class="events-feature-card">
                        <div class="events-feature-card__media">
                            @if ($featuredEvent->cover_image_url)
                                <img src="{{ $featuredEvent->cover_image_url }}" alt="{{ $featuredEvent->title }}" class="events-feature-card__image">
                            @else
                                <div class="events-feature-card__fallback"></div>
                            @endif
                        </div>

                        <div class="events-feature-card__content">
                            <div class="events-feature-card__intro">
                                <span class="eyebrow-chip">Izdvojen dogadjaj</span>
                                <div class="info-strip">
                                    <span class="info-chip-soft">{{ $featuredEvent->type->label() }}</span>
                                    <span class="info-chip-soft">{{ $featuredEvent->status->label() }}</span>
                                    @if ($featuredEvent->start_date)
                                        <span class="info-chip-soft">{{ $featuredEvent->start_date->format('d.m.Y') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="events-feature-card__body">
                                <h2 class="section-title">{{ $featuredEvent->title }}</h2>
                                @if ($featuredEvent->summary)
                                    <p class="events-feature-card__summary">{{ $featuredEvent->summary }}</p>
                                @endif
                            </div>

                            <div class="events-feature-card__stats">
                                <div class="events-feature-stat">
                                    <span class="events-feature-stat__label">Ucesnici</span>
                                    <span class="events-feature-stat__value">{{ $featuredEvent->entries_count }}</span>
                                </div>
                                <div class="events-feature-stat">
                                    <span class="events-feature-stat__label">Mecevi</span>
                                    <span class="events-feature-stat__value">{{ $featuredEvent->matches_count }}</span>
                                </div>
                                <span class="events-feature-card__cta">Pogledaj dogadjaj</span>
                            </div>
                        </div>
                    </a>
                @endif

                <div class="events-grid">
                    @foreach ($events as $event)
                        <a href="{{ route('events.show', ['event' => $event->slug]) }}" class="events-card">
                            <div class="events-card__media">
                                @if ($event->cover_image_url)
                                    <img src="{{ $event->cover_image_url }}" alt="{{ $event->title }}" class="events-card__image">
                                @else
                                    <div class="events-card__fallback"></div>
                                @endif
                            </div>

                            <div class="events-card__body">
                                <div class="events-card__top">
                                    <span class="events-card__type">{{ $event->type->label() }}</span>
                                    <span class="info-chip-soft">{{ $event->status->label() }}</span>
                                </div>

                                <h2 class="card-title">{{ $event->title }}</h2>

                                @if ($event->summary)
                                    <p class="events-card__summary">{{ $event->summary }}</p>
                                @endif

                                <div class="events-card__meta">
                                    <div class="events-card__meta-box">
                                        <span class="events-card__meta-label">Ucesnici</span>
                                        <span class="events-card__meta-value">{{ $event->entries_count }}</span>
                                    </div>
                                    <div class="events-card__meta-box">
                                        <span class="events-card__meta-label">Mecevi</span>
                                        <span class="events-card__meta-value">{{ $event->matches_count }}</span>
                                    </div>
                                </div>

                                <span class="events-card__cta">Pogledaj detalje</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
