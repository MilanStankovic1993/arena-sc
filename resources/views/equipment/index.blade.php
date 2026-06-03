@extends('layouts.site', ['title' => 'Oprema | Arena SC'])

@section('content')
    @php
        $equipmentGroups = $equipment->groupBy(fn ($item) => $item->sport?->name ?? 'Oprema');
    @endphp

    <div class="page-stack equipment-page-stack">
        <section
            class="equipment-hero"
            style="background-image: linear-gradient(90deg, rgba(7, 16, 13, 0.84) 0%, rgba(7, 16, 13, 0.48) 44%, rgba(7, 16, 13, 0.82) 100%), url('{{ asset('media/home/equipment-hero.png') }}');"
        >
            <div class="site-grid equipment-hero__inner">
                <div class="equipment-hero__content">
                    <span class="dark-eyebrow-chip">Oprema</span>
                    <h1 class="hero-title-dark max-w-4xl">OPREMA ZA TERMIN, IZNJAMLJIVANJE I PRODAJU NA JEDNOM MESTU.</h1>
                    <div class="equipment-hero__chips">
                        <span class="info-chip-soft-dark">{{ $equipment->count() }} artikala</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="site-grid pb-10 sm:pb-14">
            <div class="sports-section-stack">
                @foreach ($equipmentGroups as $groupName => $items)
                    <section class="sports-showcase">
                        <div class="sports-showcase__intro">
                            <span class="eyebrow-chip">{{ $groupName }}</span>
                            <h2 class="sports-showcase__title mt-5">{{ $groupName }} OPREMA.</h2>
                        </div>

                        <div class="equipment-cards-grid">
                            @foreach ($items as $item)
                                <article class="equipment-feature-card">
                                    <div class="equipment-feature-card__media">
                                        @if ($item->image_url)
                                            <img
                                                src="{{ $item->image_url }}"
                                                alt="{{ $item->name }}"
                                                class="equipment-feature-card__image"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="equipment-feature-card__fallback"></div>
                                        @endif
                                    </div>

                                    <div class="equipment-feature-card__body">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="sports-court-card__sport">{{ $item->sport?->name ?? 'Oprema' }}</p>
                                                <h3 class="card-title mt-3">{{ $item->name }}</h3>
                                            </div>
                                            <span class="info-chip">{{ $item->stock_quantity }} kom</span>
                                        </div>

                                        @if ($item->short_description)
                                            <p class="equipment-feature-card__description">{{ $item->short_description }}</p>
                                        @endif

                                        <div class="equipment-feature-card__prices">
                                            @if ($item->is_rentable)
                                                <div class="equipment-price-box">
                                                    <p class="equipment-price-box__label">Iznajmljivanje</p>
                                                    <p class="equipment-price-box__value">{{ number_format($item->rental_price, 0, ',', '.') }} RSD</p>
                                                </div>
                                            @endif

                                            @if ($item->is_sellable)
                                                <div class="equipment-price-box equipment-price-box--accent">
                                                    <p class="equipment-price-box__label">Prodaja</p>
                                                    <p class="equipment-price-box__value">{{ number_format($item->sale_price, 0, ',', '.') }} RSD</p>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="info-strip">
                                            @if ($item->is_rentable)
                                                <span class="info-chip">Moze uz termin</span>
                                            @endif
                                            @if ($item->is_sellable)
                                                <span class="info-chip">Dostupno za prodaju</span>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
    </div>
@endsection
