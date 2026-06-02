@extends('layouts.site', ['title' => $court->name . ' | Arena SC'])

@section('content')
    <section class="site-grid py-10 sm:py-12">
        <div class="page-stack">
            <div class="page-hero-dark overflow-hidden">
                <div class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr]">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-sand)]">{{ $court->sport->name }}</p>
                        <h1 class="mt-3 text-5xl font-black sm:text-6xl">{{ $court->name }}</h1>
                        <p class="mt-5 max-w-3xl text-base leading-8 text-white/75">{{ $court->description }}</p>
                    </div>

                    <div class="metric-ribbon sm:grid-cols-3 lg:grid-cols-1">
                        <div class="dark-metric-ribbon-card">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-white/55">Lokacija</p>
                            <p class="mt-2 font-semibold">{{ $court->location }}</p>
                        </div>
                        <div class="dark-metric-ribbon-card">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-white/55">Podloga</p>
                            <p class="mt-2 font-semibold">{{ $court->surface }}</p>
                        </div>
                        <div class="dark-metric-ribbon-card">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-white/55">Rezervacija</p>
                            <p class="mt-2 font-semibold">Provera po terenu</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="split-surface">
                <div class="grid gap-8 xl:grid-cols-[1.05fr_1.2fr]">
                <div class="premium-card p-6 sm:p-7">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <span class="eyebrow-chip">Cenovnik po sportu</span>
                            <h2 class="section-title mt-4">Jasna tabela cena za svaki vremenski blok.</h2>
                        </div>
                        <span class="info-chip">
                            {{ $court->sport->name }}
                        </span>
                    </div>

                    <div class="site-table-shell mt-6 overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-extrabold uppercase tracking-[0.22em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-4">Blok</th>
                                    <th class="px-4 py-4">Dani</th>
                                    <th class="px-4 py-4">1h</th>
                                    <th class="px-4 py-4">1,5h</th>
                                    <th class="px-4 py-4">2h</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse ($pricingRules as $rule)
                                    <tr>
                                        <td class="px-4 py-4">
                                            <p class="font-semibold text-[color:var(--arena-forest)]">{{ $rule->name }}</p>
                                            <p class="text-xs text-slate-500">{{ substr($rule->start_time, 0, 5) }} - {{ substr($rule->end_time, 0, 5) }}</p>
                                        </td>
                                        <td class="px-4 py-4 text-slate-600">{{ $rule->days_label }}</td>
                                        <td class="px-4 py-4 font-semibold">{{ number_format($rule->price_60, 0, ',', '.') }} RSD</td>
                                        <td class="px-4 py-4 font-semibold">{{ number_format($rule->price_90, 0, ',', '.') }} RSD</td>
                                        <td class="px-4 py-4 font-semibold">{{ number_format($rule->price_120, 0, ',', '.') }} RSD</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                            Cenovnik jos nije definisan. Sistem ce privremeno prikazati podrazumevanu cenu rezervacije.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="premium-card p-6 sm:p-7">
                    <p class="text-sm font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">Rezervacija</p>
                    <h2 class="section-title mt-4">Za rezervaciju koristi centralni planer termina.</h2>
                    <p class="hero-copy mt-4 text-sm">
                        Detalj terena sada je informativan, a rezervacija ide kroz jedan cist planer. Prvo biras sport, zatim konkretan teren, pa sistem prikazuje samo slobodne termine bas za taj teren.
                    </p>

                    <div class="premium-grid mt-6 sm:grid-cols-3">
                        <div class="premium-card bg-[color:var(--arena-paper)] p-4">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Korak 1</p>
                            <p class="mt-2 font-semibold text-[color:var(--arena-forest)]">Izaberi sport</p>
                        </div>
                        <div class="premium-card bg-[color:var(--arena-paper)] p-4">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Korak 2</p>
                            <p class="mt-2 font-semibold text-[color:var(--arena-forest)]">Izaberi teren</p>
                        </div>
                        <div class="premium-card bg-[color:var(--arena-paper)] p-4">
                            <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[color:var(--arena-muted)]">Korak 3</p>
                            <p class="mt-2 font-semibold text-[color:var(--arena-forest)]">Uzmi slobodan termin</p>
                        </div>
                    </div>

                    <div class="soft-message mt-6">
                        <p class="text-sm leading-7 text-[color:var(--arena-ink)]">
                            Ako isti sport ima vise terena, dostupnost se proverava posebno za svaki teren. To znaci da dva padel terena mogu biti zauzeta, a treci i dalje slobodan za isti vremenski slot.
                        </p>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('booking.index', ['sport' => $court->sport->slug, 'court' => $court->slug]) }}" class="arena-button-primary">Rezervisi ovaj teren</a>
                        <a href="{{ route('booking.index', ['sport' => $court->sport->slug]) }}" class="arena-button-secondary">Pogledaj sve terene za {{ $court->sport->name }}</a>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </section>
@endsection
