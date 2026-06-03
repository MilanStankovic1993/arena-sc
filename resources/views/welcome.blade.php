@extends('layouts.site', ['title' => 'Arena SC'])

@section('content')
    <div class="page-stack home-page-stack">
        <section class="home-hero" style="background-image: linear-gradient(90deg, rgba(7, 16, 13, 0.74) 0%, rgba(7, 16, 13, 0.42) 44%, rgba(7, 16, 13, 0.72) 100%), url('{{ asset('media/home/hero-exterior.png') }}');">
            <div class="site-grid home-hero__inner">
                <div class="home-hero__content">
                    <span class="dark-eyebrow-chip">Sportski centar Arena</span>
                    <h1 class="home-hero__title">REZERVISI TERMIN</h1>
                    <div class="mt-6">
                        <a href="{{ route('booking.index') }}" class="arena-button-primary">Rezervisi termin</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="site-grid">
            <div class="home-split-feature">
                <div class="home-split-feature__copy">
                    <span class="eyebrow-chip">Sve na jednom mestu</span>
                    <h2 class="section-title mt-5">PADEL, BASKET 3X3 I RESTORAN SA PREKO 100 MESTA.</h2>
                </div>

                <div class="home-split-feature__visual">
                    <img src="{{ asset('media/home/all-in-one.png') }}" alt="Sportski centar Arena - vise sadrzaja na jednom mestu" class="home-section-image">
                </div>
            </div>
        </section>

        <section class="home-courts-cta" style="background-image: linear-gradient(90deg, rgba(6, 12, 10, 0.82) 0%, rgba(6, 12, 10, 0.54) 46%, rgba(6, 12, 10, 0.82) 100%), url('{{ asset('media/home/courts-night.png') }}');">
            <div class="site-grid home-courts-cta__inner">
                <div class="home-courts-cta__content">
                    <span class="dark-eyebrow-chip">Tereni</span>
                    <h2 class="section-title-dark mt-5">PROSTOR ZA PADEL I BASKET 3X3 U JEDNOM PREMIUM CENTRU.</h2>
                    <div class="mt-7">
                        <a href="{{ route('sports.index') }}" class="arena-button-primary">Pogledaj terene</a>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
