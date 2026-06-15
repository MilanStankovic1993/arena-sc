@extends('layouts.site', [
    'title' => 'Cenovnik termina i clanarina | Sportski centar Arena',
    'metaDescription' => 'Cenovnik termina za padel i basket 3x3, clanarine i pravila rezervacije u Sportski centar Arena Kraljevo.',
    'metaKeywords' => 'cenovnik padel, cenovnik basket 3x3, clanarine, sportski centar kraljevo, arena kraljevo, rezervacija termina',
    'metaImage' => asset('media/home/equipment-hero.png'),
])

@section('content')
    @php
        $pricingGroups = $pricingRules->groupBy(fn ($rule) => $rule->sport?->name ?? 'Termini');
        $membershipGroups = $membershipPlans->groupBy(fn ($plan) => $plan->sport?->name ?? 'Sve clanarine');

        $formatPrice = fn ($price): string => number_format((float) $price, 0, ',', '.') . ' RSD';
        $formatTime = fn ($time): string => substr((string) $time, 0, 5);
    @endphp

    <div class="page-stack equipment-page-stack">
        <section
            class="equipment-hero"
            style="background-image: linear-gradient(90deg, rgba(7, 16, 13, 0.86) 0%, rgba(7, 16, 13, 0.46) 44%, rgba(7, 16, 13, 0.84) 100%), url('{{ asset('media/home/equipment-hero.png') }}');"
        >
            <div class="site-grid equipment-hero__inner">
                <div class="equipment-hero__content">
                    <span class="dark-eyebrow-chip">Cenovnik</span>
                    <h1 class="hero-title-dark max-w-5xl">CENE TERMINA I CLANARINE U JEDNOM PREGLEDU.</h1>
                    <div class="equipment-hero__chips">
                        <span class="info-chip-soft-dark">{{ $pricingRules->count() }} cenovnih blokova</span>
                        <span class="info-chip-soft-dark">{{ $membershipPlans->count() }} clanarina</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="site-grid">
            <div class="price-nav-grid price-nav-grid--compact">
                <a href="#termini" class="price-nav-card">
                    <span>01</span>
                    <strong>Cene termina</strong>
                    <small>Blokovi po sportu i vremenu</small>
                </a>
                <a href="#clanarine" class="price-nav-card">
                    <span>02</span>
                    <strong>Clanarina</strong>
                    <small>Planovi i broj rezervacija</small>
                </a>
            </div>
        </section>

        <section id="termini" class="site-grid price-section-anchor pb-4 sm:pb-8">
            <div class="sports-section-stack">
                <div class="sports-showcase__intro">
                    <span class="eyebrow-chip">Cene termina</span>
                    <h2 class="sports-showcase__title mt-5">CENE TERMINA PO SPORTU.</h2>
                </div>

                @forelse ($pricingGroups as $groupName => $rules)
                    <section class="pricing-panel">
                        <div class="pricing-panel__header">
                            <div>
                                <p class="sports-court-card__sport">{{ $groupName }}</p>
                                <h3>{{ $groupName }} termini</h3>
                            </div>
                            <span class="info-chip">{{ $rules->count() }} blokova</span>
                        </div>

                        <div class="pricing-table-shell">
                            <table class="pricing-table">
                                <thead>
                                    <tr>
                                        <th>Blok</th>
                                        <th>Vreme</th>
                                        <th>1h</th>
                                        <th>1,5h</th>
                                        <th>2h</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rules as $rule)
                                        <tr>
                                            <td>
                                                <strong>{{ $rule->name }}</strong>
                                            </td>
                                            <td>{{ $formatTime($rule->start_time) }} - {{ $formatTime($rule->end_time) }}</td>
                                            <td>{{ $formatPrice($rule->price_60) }}</td>
                                            <td>{{ $formatPrice($rule->price_90) }}</td>
                                            <td>{{ $formatPrice($rule->price_120) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @empty
                    <div class="price-empty-card">
                        <span class="eyebrow-chip">Termini</span>
                        <h2>Cene termina ce biti prikazane kada ih definises u admin panelu.</h2>
                    </div>
                @endforelse
            </div>
        </section>

        <section id="clanarine" class="site-grid price-section-anchor pb-10 sm:pb-14">
            <div class="sports-section-stack">
                <div class="sports-showcase__intro">
                    <span class="eyebrow-chip">Clanarina</span>
                    <h2 class="sports-showcase__title mt-5">CLANARINE I PRAVILA REZERVACIJE.</h2>
                </div>

                @forelse ($membershipGroups as $groupName => $plans)
                    <section class="membership-panel">
                        <div class="pricing-panel__header">
                            <div>
                                <p class="sports-court-card__sport">{{ $groupName }}</p>
                                <h3>{{ $groupName }}</h3>
                            </div>
                            <span class="info-chip">{{ $plans->count() }} paketa</span>
                        </div>

                        <div class="membership-grid">
                            @foreach ($plans as $plan)
                                <article class="membership-card">
                                    <div>
                                        <p class="sports-court-card__sport">{{ $plan->sport?->name ?? 'Svi sportovi' }}</p>
                                        <h3>{{ $plan->name }}</h3>
                                        @if ($plan->period_label)
                                            <span class="membership-card__period">{{ $plan->period_label }}</span>
                                        @endif
                                    </div>

                                    <div class="membership-card__price">{{ $formatPrice($plan->price) }}</div>

                                    <div class="membership-card__meta">
                                        <div>
                                            <span>Rezervacije</span>
                                            <strong>{{ $plan->reservation_limit }} termina ukupno</strong>
                                        </div>
                                        <div>
                                            <span>Trajanje</span>
                                            <strong>{{ $plan->duration_days ? $plan->duration_days . ' dana' : 'Po dogovoru' }}</strong>
                                        </div>
                                    </div>

                                    @if ($plan->short_description || $plan->description)
                                        <p>{{ $plan->short_description ?: $plan->description }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="price-empty-card">
                        <span class="eyebrow-chip">Clanarina</span>
                        <h2>Clanarine ce biti prikazane kada ih definises u admin panelu.</h2>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
