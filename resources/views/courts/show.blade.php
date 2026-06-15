@extends('layouts.site', [
    'title' => $court->name . ' | ' . $court->sport->name . ' | Arena Kraljevo',
    'metaDescription' => trim(($court->description ?: $court->sport->name . ' teren u Sportski centar Arena Kraljevo.') . ' Rezervisite termin online i pogledajte cenovnik.'),
    'metaKeywords' => strtolower($court->name . ', ' . $court->sport->name . ', teren, rezervacija terena, kraljevo padel, kraljevo basket, sportski centar arena'),
    'metaImage' => $court->image_url ?: asset('media/home/courts-night.png'),
    'metaType' => 'article',
])

@section('content')
    <div class="page-stack">
        <section
            class="court-hero"
            @if ($court->image_url)
                style="background-image: linear-gradient(180deg, rgba(7, 16, 13, 0.20) 0%, rgba(7, 16, 13, 0.58) 48%, rgba(7, 16, 13, 0.88) 100%), url('{{ $court->image_url }}');"
            @endif
        >
            <div class="site-grid court-hero__inner">
                <div class="court-hero__content">
                    <span class="dark-eyebrow-chip">{{ $court->sport->name }}</span>
                    <h1 class="court-hero__title">{{ $court->name }}</h1>

                    <div class="court-hero__chips">
                        <span class="info-chip-soft-dark">{{ $court->location }}</span>
                        <span class="info-chip-soft-dark">{{ $court->surface }}</span>
                    </div>

                    <div class="court-hero__actions">
                        <a href="{{ route('booking.index', ['sport' => $court->sport->slug, 'court' => $court->slug]) }}" class="arena-button-primary">
                            Rezervisi termin za ovaj teren
                        </a>
                        <a href="{{ route('sports.index') }}" class="arena-button-secondary">
                            Nazad na terene
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
