@extends('layouts.site', ['title' => 'Oprema | Arena SC'])

@section('content')
    <section class="site-grid py-10 sm:py-12">
        <div class="page-stack">
            <div class="page-hero">
                <span class="eyebrow-chip">Katalog opreme</span>
                <div class="hero-grid mt-6 items-end">
                    <div>
                        <h1 class="hero-title max-w-4xl text-5xl sm:text-6xl">Oprema za prodaju i iznajmljivanje u istom premium tonu.</h1>
                        <p class="hero-copy mt-5 max-w-2xl">
                            Korisnik ovde jasno vidi sta moze da kupi, a sta moze da doda uz rezervaciju termina kao dodatnu opremu.
                        </p>
                    </div>
                    <div class="metric-ribbon">
                        <div class="metric-ribbon-card">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Artikli</p>
                            <p class="mt-3 text-3xl font-black text-[color:var(--arena-forest)]">{{ $equipment->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="premium-grid md:grid-cols-2 xl:grid-cols-3">
                @foreach ($equipment as $item)
                    <article class="premium-card overflow-hidden p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">{{ $item->sport?->name ?? 'Oprema' }}</p>
                                <h2 class="mt-3 text-2xl font-black text-[color:var(--arena-forest)]">{{ $item->name }}</h2>
                            </div>
                            <span class="info-chip">{{ $item->stock_quantity }} kom</span>
                        </div>

                        <p class="mt-4 text-sm leading-7 text-[color:var(--arena-muted)]">{{ $item->short_description }}</p>

                        <div class="premium-grid mt-6 sm:grid-cols-2">
                            <div class="premium-card bg-[color:var(--arena-paper)] p-4">
                                <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Iznajmljivanje</p>
                                <p class="mt-2 text-xl font-black text-[color:var(--arena-forest)]">{{ number_format($item->rental_price, 0, ',', '.') }} RSD</p>
                            </div>
                            <div class="premium-card bg-[color:var(--arena-sand-soft)] p-4">
                                <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Prodaja</p>
                                <p class="mt-2 text-xl font-black text-[color:var(--arena-forest)]">{{ number_format($item->sale_price, 0, ',', '.') }} RSD</p>
                            </div>
                        </div>

                        <div class="info-strip mt-6">
                            @if ($item->is_rentable)
                                <span class="info-chip">Moze uz termin</span>
                            @endif
                            @if ($item->is_sellable)
                                <span class="info-chip-soft">Dostupno za prodaju</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
