@extends('layouts.site', [
    'title' => $event->title . ' | Dogadjaji | Arena Kraljevo',
    'metaDescription' => trim(($event->summary ?: $event->description ?: 'Turnir ili liga u Sportski centar Arena Kraljevo.') . ' Pogledajte ucesnike, raspored i rezultate.'),
    'metaKeywords' => strtolower($event->title . ', ' . $event->type->label() . ', padel turnir, padel liga, basket 3x3, sportski dogadjaji kraljevo'),
    'metaImage' => $event->cover_image_url ?: asset('media/home/events-hero.png'),
    'metaType' => 'article',
])

@section('content')
    <div class="page-stack">
        <section
            class="event-detail-hero"
            style="background-image: linear-gradient(180deg, rgba(7, 16, 13, 0.62) 0%, rgba(7, 16, 13, 0.78) 48%, rgba(7, 16, 13, 0.9) 100%), url('{{ $event->cover_image_url ?: asset('media/home/events-hero.png') }}');"
        >
            <div class="site-grid event-detail-hero__inner">
                <div class="event-detail-hero__content">
                    <span class="dark-eyebrow-chip">{{ $event->type->label() }}</span>
                    <h1 class="hero-title-dark max-w-4xl">{{ $event->title }}</h1>

                    <div class="event-detail-hero__chips">
                        <span class="info-chip-soft-dark">{{ $event->status->label() }}</span>
                        @if ($event->start_date)
                            <span class="info-chip-soft-dark">{{ $event->start_date->format('d.m.Y') }}</span>
                        @endif
                        <span class="info-chip-soft-dark">{{ $event->entries_count }} ucesnika</span>
                        <span class="info-chip-soft-dark">{{ $event->matches_count }} meceva</span>
                    </div>

                    @if ($event->description ?: $event->summary)
                        <p class="hero-copy-dark max-w-3xl">{{ $event->description ?: $event->summary }}</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="site-grid pb-10 sm:pb-14">
            <div class="events-detail-stack">
                <div class="events-detail-grid">
                    <div class="premium-card p-6 sm:p-8">
                        <span class="eyebrow-chip">Pravila</span>
                        <h2 class="section-title mt-5">Pravila i format</h2>
                        <p class="events-detail-copy mt-6">
                            {{ $event->rules ?: 'Ovde ide tekst pravila turnira ili lige. Dodaj pravila kroz admin panel kada budu finalizovana.' }}
                        </p>
                    </div>

                    <div class="premium-card p-6 sm:p-8">
                        <span class="eyebrow-chip">Pregled</span>
                        <div class="events-detail-summary-grid mt-6">
                            <div class="events-detail-summary-box">
                                <span class="events-detail-summary-box__label">Status</span>
                                <span class="events-detail-summary-box__value">{{ $event->status->label() }}</span>
                            </div>
                            <div class="events-detail-summary-box">
                                <span class="events-detail-summary-box__label">Tip</span>
                                <span class="events-detail-summary-box__value">{{ $event->type->label() }}</span>
                            </div>
                            <div class="events-detail-summary-box">
                                <span class="events-detail-summary-box__label">Ucesnici</span>
                                <span class="events-detail-summary-box__value">{{ $summary['entries_count'] }}</span>
                            </div>
                            <div class="events-detail-summary-box">
                                <span class="events-detail-summary-box__label">Mecevi</span>
                                <span class="events-detail-summary-box__value">{{ $summary['matches_count'] }}</span>
                            </div>
                            <div class="events-detail-summary-box">
                                <span class="events-detail-summary-box__label">Zavrseni</span>
                                <span class="events-detail-summary-box__value">{{ $summary['finished_matches_count'] }}</span>
                            </div>
                            <div class="events-detail-summary-box">
                                <span class="events-detail-summary-box__label">Zakazani</span>
                                <span class="events-detail-summary-box__value">{{ $summary['scheduled_matches_count'] }}</span>
                            </div>
                            @if ($summary['leader_name'])
                                <div class="events-detail-summary-box">
                                    <span class="events-detail-summary-box__label">{{ $event->isLeague() ? 'Lider' : 'Najuspesniji' }}</span>
                                    <span class="events-detail-summary-box__value">{{ $summary['leader_name'] }}</span>
                                </div>
                            @endif
                            @if ($summary['leader_points'] !== null)
                                <div class="events-detail-summary-box">
                                    <span class="events-detail-summary-box__label">Poeni</span>
                                    <span class="events-detail-summary-box__value">{{ $summary['leader_points'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="events-detail-grid">
                    <div class="premium-card p-6 sm:p-8">
                        <span class="eyebrow-chip">{{ $event->isLeague() ? 'Tabela lige' : 'Statistika ucesnika' }}</span>
                        <h2 class="section-title mt-5">{{ $event->isLeague() ? 'Tabela i plasman' : 'Ucesnici i ucinak' }}</h2>

                        <div class="site-table-shell mt-6 overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-extrabold uppercase tracking-[0.22em] text-slate-500">
                                    <tr>
                                        <th class="px-4 py-4">#</th>
                                        <th class="px-4 py-4">Tim ili par</th>
                                        <th class="px-4 py-4">P</th>
                                        <th class="px-4 py-4">W</th>
                                        <th class="px-4 py-4">D</th>
                                        <th class="px-4 py-4">L</th>
                                        <th class="px-4 py-4">+/−</th>
                                        <th class="px-4 py-4">Pts</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    @forelse ($standings as $entry)
                                        <tr>
                                            <td class="px-4 py-4 font-bold text-[color:var(--arena-forest-glow)]">{{ $entry['position'] }}</td>
                                            <td class="px-4 py-4">
                                                <p class="font-semibold text-[color:var(--arena-forest)]">{{ $entry['team_name'] }}</p>
                                                @if ($entry['user_name'])
                                                    <p class="text-xs text-slate-500">Korisnik: {{ $entry['user_name'] }}</p>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4">{{ $entry['played'] }}</td>
                                            <td class="px-4 py-4">{{ $entry['wins'] }}</td>
                                            <td class="px-4 py-4">{{ $entry['draws'] }}</td>
                                            <td class="px-4 py-4">{{ $entry['losses'] }}</td>
                                            <td class="px-4 py-4">{{ $entry['score_for'] }} / {{ $entry['score_against'] }}</td>
                                            <td class="px-4 py-4 font-bold text-[color:var(--arena-forest-glow)]">{{ $entry['points'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="px-4 py-6 text-sm text-slate-500">Jos nema dodatih ucesnika.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="premium-card p-6 sm:p-8">
                        <span class="eyebrow-chip">{{ $event->isLeague() ? 'Kola i mecevi' : 'Faze turnira' }}</span>
                        <h2 class="section-title mt-5">{{ $event->isLeague() ? 'Raspored i rezultati lige' : 'Raspored i rezultati turnira' }}</h2>

                        <div class="events-matches-stack mt-6">
                            @forelse ($matchGroups as $group)
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-extrabold uppercase tracking-[0.28em] text-[color:var(--arena-forest-glow)]">{{ $group['round'] }}</p>
                                        <span class="info-chip">{{ count($group['matches']) }} meceva</span>
                                    </div>

                                    @foreach ($group['matches'] as $match)
                                        <div class="events-match-card">
                                            <div>
                                                <p class="events-match-card__round">{{ $match->round_label ?: 'Mec' }}</p>
                                                <p class="events-match-card__teams">
                                                    {{ $match->homeEntry?->team_name ?? 'TBD' }} vs {{ $match->awayEntry?->team_name ?? 'TBD' }}
                                                </p>
                                                <p class="events-match-card__time">{{ optional($match->scheduled_at)->format('d.m.Y H:i') ?: 'Termin uskoro' }}</p>
                                            </div>

                                            <div class="events-match-card__score-wrap">
                                                <span class="info-chip">
                                                    @switch($match->status)
                                                        @case('finished') Zavrsen @break
                                                        @case('cancelled') Otkazan @break
                                                        @default Zakazan
                                                    @endswitch
                                                </span>
                                                @if (! is_null($match->home_score) && ! is_null($match->away_score))
                                                    <p class="events-match-card__score">{{ $match->home_score }} : {{ $match->away_score }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <p class="events-empty-copy">Jos nema unetih meceva za ovaj dogadjaj.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
