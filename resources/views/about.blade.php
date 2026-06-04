@extends('layouts.site', ['title' => 'O nama | Arena SC'])

@section('content')
    @php
        $locationName = config('arena.location.name');
        $locationLabel = config('arena.location.label');
        $mapsUrl = config('arena.location.maps_url');
        $mapEmbedUrl = config('arena.location.map_embed_url');
    @endphp
    <div class="page-stack about-page-stack">
        <section
            class="about-hero"
            style="background-image: linear-gradient(90deg, rgba(7, 16, 13, 0.86) 0%, rgba(7, 16, 13, 0.46) 42%, rgba(7, 16, 13, 0.78) 100%), url('{{ asset('media/home/about-hero.png') }}');"
        >
            <div class="site-grid about-hero__inner">
                <div class="about-hero__content">
                    <span class="dark-eyebrow-chip">O nama</span>
                    <h1 class="hero-title-dark max-w-4xl">PROSTOR U KOME SE SPORT, AMBIJENT I ORGANIZACIJA SPOJE U JEDNO.</h1>
                </div>
            </div>
        </section>

        <section class="site-grid pb-10 sm:pb-14">
            <div class="about-section-stack">
                <div
                    class="about-story-panel"
                    style="background-image: linear-gradient(90deg, rgba(245, 245, 242, 0.96) 0%, rgba(245, 245, 242, 0.88) 42%, rgba(245, 245, 242, 0.16) 100%), url('{{ asset('media/home/about-story.png') }}');"
                >
                    <div class="about-story-panel__copy">
                        <span class="eyebrow-chip">Sportski centar Arena</span>
                        <h2 class="section-title mt-5">Ovde ide tekst o nama.</h2>
                        <p class="about-story-panel__text mt-6">
                            Ovde ide tekst o nama.
                        </p>
                    </div>
                </div>

                <div class="about-location-card">
                    <div class="about-location-card__copy">
                        <span class="eyebrow-chip">Lokacija</span>
                        <h2 class="section-title-dark mt-5">Nalazimo se na jednoj lokaciji za padel, basket 3x3 i kompletan premium ambijent.</h2>
                        <p class="about-location-card__text mt-6">
                            Pogledaj gde se nalazimo i otvori direktnu navigaciju do sportskog centra.
                        </p>

                        <a
                            href="{{ $mapsUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="arena-button-primary"
                        >
                            Otvori Google mapu
                        </a>
                    </div>

                    <div class="about-location-card__map-shell">
                        <iframe
                            src="{{ $mapEmbedUrl }}"
                            loading="lazy"
                            class="about-location-card__map"
                            title="Lokacija {{ $locationName }}"
                        ></iframe>

                        <div class="about-location-card__map-meta">
                            <div>
                                <span class="dark-eyebrow-chip">Lokacija na mapi</span>
                                <h3 class="card-title-dark mt-4">{{ $locationName }}</h3>
                                <p class="about-location-card__map-text">{{ $locationLabel }}</p>
                            </div>

                            <a
                                href="{{ $mapsUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="about-location-card__map-cta"
                            >
                                Otvori Google Maps
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
