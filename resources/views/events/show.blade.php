@extends('layouts.site', ['title' => $event->title . ' | Arena SC'])

@section('content')
    <section class="site-grid py-10 sm:py-12">
        <div class="page-stack">
        <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="space-y-6">
                <div class="page-hero-dark overflow-hidden">
                    <p class="text-xs font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-sand)]">{{ $event->type->label() }}</p>
                    <h1 class="mt-3 text-5xl font-black sm:text-6xl">{{ $event->title }}</h1>
                    <p class="mt-5 text-base leading-8 text-white/75">{{ $event->description ?: $event->summary }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <span class="info-chip-soft">{{ $event->status->label() }}</span>
                        @if ($event->start_date)
                            <span class="info-chip-soft">{{ $event->start_date->format('d.m.Y') }}</span>
                        @endif
                    </div>
                </div>

                <div class="premium-card p-6 sm:p-7">
                    <span class="eyebrow-chip">Pravila</span>
                    <p class="mt-4 text-sm leading-8 text-[color:var(--arena-muted)]">{{ $event->rules ?: 'Ovde ce biti prikazana pravila turnira ili lige kada ih uneses kroz admin panel.' }}</p>
                </div>
            </div>

            <div class="space-y-6">
                <div class="premium-card p-6 sm:p-7">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">Tabela i ucesnici</p>
                            <h2 class="hero-title mt-4 text-3xl sm:text-4xl">Pregled statistike</h2>
                        </div>
                    </div>

                    <div class="site-table-shell mt-6 overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-extrabold uppercase tracking-[0.22em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-4">Tim ili par</th>
                                    <th class="px-4 py-4">P</th>
                                    <th class="px-4 py-4">W</th>
                                    <th class="px-4 py-4">L</th>
                                    <th class="px-4 py-4">Pts</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse ($event->entries as $entry)
                                    <tr>
                                        <td class="px-4 py-4 font-semibold text-[color:var(--arena-forest)]">{{ $entry->team_name }}</td>
                                        <td class="px-4 py-4">{{ $entry->played }}</td>
                                        <td class="px-4 py-4">{{ $entry->wins }}</td>
                                        <td class="px-4 py-4">{{ $entry->losses }}</td>
                                        <td class="px-4 py-4 font-bold text-[color:var(--arena-forest-glow)]">{{ $entry->points }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-sm text-slate-500">Jos nema dodatih ucesnika.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="premium-card p-6 sm:p-7">
                    <p class="text-sm font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">Mecevi i rezultati</p>
                    <div class="mt-5 space-y-4">
                        @forelse ($event->matches as $match)
                            <div class="premium-card bg-[color:var(--arena-paper)] p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-slate-500">{{ $match->round_label ?: 'Mec' }}</p>
                                        <p class="mt-2 font-black text-[color:var(--arena-forest)]">
                                            {{ $match->homeEntry?->team_name ?? 'TBD' }} vs {{ $match->awayEntry?->team_name ?? 'TBD' }}
                                        </p>
                                        <p class="mt-2 text-sm text-slate-500">{{ optional($match->scheduled_at)->format('d.m.Y H:i') ?: 'Termin uskoro' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="info-chip">{{ ucfirst($match->status) }}</span>
                                        @if (! is_null($match->home_score) && ! is_null($match->away_score))
                                            <p class="mt-3 text-2xl font-black text-[color:var(--arena-forest-glow)]">{{ $match->home_score }} : {{ $match->away_score }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Jos nema unetih meceva za ovaj dogadjaj.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        </div>
    </section>
@endsection
