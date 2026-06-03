@extends('layouts.site', ['title' => 'Tereni i sportovi | Arena SC'])

@section('content')
    <div class="page-stack sports-page-stack">
        <section class="sports-hero" style="background-image: linear-gradient(90deg, rgba(7, 16, 13, 0.82) 0%, rgba(7, 16, 13, 0.48) 44%, rgba(7, 16, 13, 0.82) 100%), url('{{ asset('media/home/sports-hero.png') }}');">
            <div class="site-grid sports-hero__inner">
                <div class="sports-hero__content">
                    <span class="dark-eyebrow-chip">Tereni</span>
                    <h1 class="hero-title-dark max-w-4xl">RASPOLAZEMO PADEL TERENIMA I TERENIMA ZA BASKET 3X3.</h1>
                </div>
            </div>
        </section>

        <section class="site-grid pb-10 sm:pb-14">
            <div class="sports-section-stack">
                @foreach ($sports as $sport)
                    <section class="sports-showcase">
                        <div class="sports-showcase__intro">
                            <span class="eyebrow-chip">{{ $sport->name }}</span>
                            <h2 class="sports-showcase__title mt-5">{{ $sport->name }} TERENI.</h2>
                            @if ($sport->short_description ?: $sport->description)
                                <p class="sports-showcase__copy mt-5">{{ $sport->short_description ?: $sport->description }}</p>
                            @endif
                        </div>

                        <div class="sports-cards-grid">
                            @foreach ($sport->courts as $court)
                                <a
                                    href="{{ route('courts.show', ['court' => $court->slug]) }}"
                                    class="sports-court-card"
                                >
                                    @if ($court->image_url)
                                        <img
                                            src="{{ $court->image_url }}"
                                            alt="{{ $court->name }}"
                                            class="sports-court-card__image"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="sports-court-card__fallback"></div>
                                    @endif

                                    <div class="sports-court-card__overlay">
                                        <div class="space-y-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p class="sports-court-card__sport">{{ $sport->name }}</p>
                                                    <h3 class="card-title-dark mt-3">{{ $court->name }}</h3>
                                                </div>
                                                <span class="info-chip-soft-dark">{{ $court->surface }}</span>
                                            </div>

                                            @if ($court->description)
                                                <p class="sports-court-card__description">{{ $court->description }}</p>
                                            @endif
                                        </div>

                                        <div class="flex items-center justify-between gap-3">
                                            <span class="info-chip-soft-dark">{{ $court->location }}</span>
                                            <span class="sports-court-card__cta">Pogledaj teren</span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
    </div>
@endsection
