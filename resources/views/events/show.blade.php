@extends('layouts.site', ['title' => $event->title . ' | Arena SC'])

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="space-y-6">
                <div class="site-card overflow-hidden bg-[linear-gradient(145deg,rgba(13,59,102,0.96),rgba(215,38,61,0.82))] p-8 text-white">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-blue-100">{{ $event->type->label() }}</p>
                    <h1 class="mt-3 text-5xl font-black">{{ $event->title }}</h1>
                    <p class="mt-5 text-base leading-8 text-white/75">{{ $event->description ?: $event->summary }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em]">{{ $event->status->label() }}</span>
                        @if ($event->start_date)
                            <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em]">{{ $event->start_date->format('d.m.Y') }}</span>
                        @endif
                    </div>
                </div>

                <div class="site-card p-6">
                    <p class="text-sm font-bold uppercase tracking-[0.3em] text-[var(--arena-blue-soft)]">Pravila</p>
                    <p class="mt-4 text-sm leading-8 text-slate-600">{{ $event->rules ?: 'Ovde će biti prikazana pravila turnira ili lige kada ih uneseš kroz admin panel.' }}</p>
                </div>
            </div>

            <div class="space-y-6">
                <div class="site-card p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.3em] text-[var(--arena-red)]">Tabela / ucesnici</p>
                            <h2 class="mt-3 text-3xl font-black text-[var(--arena-blue)]">Pregled statistike</h2>
                        </div>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-[0.22em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-4">Tim / par</th>
                                    <th class="px-4 py-4">P</th>
                                    <th class="px-4 py-4">W</th>
                                    <th class="px-4 py-4">L</th>
                                    <th class="px-4 py-4">Pts</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @forelse ($event->entries as $entry)
                                    <tr>
                                        <td class="px-4 py-4 font-semibold text-[var(--arena-blue)]">{{ $entry->team_name }}</td>
                                        <td class="px-4 py-4">{{ $entry->played }}</td>
                                        <td class="px-4 py-4">{{ $entry->wins }}</td>
                                        <td class="px-4 py-4">{{ $entry->losses }}</td>
                                        <td class="px-4 py-4 font-bold text-[var(--arena-red)]">{{ $entry->points }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-sm text-slate-500">Još nema dodatih učesnika.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="site-card p-6">
                    <p class="text-sm font-bold uppercase tracking-[0.3em] text-[var(--arena-red)]">Mecevi i rezultati</p>
                    <div class="mt-5 space-y-4">
                        @forelse ($event->matches as $match)
                            <div class="rounded-[1.5rem] border border-slate-200 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">{{ $match->round_label ?: 'Mec' }}</p>
                                        <p class="mt-2 font-black text-[var(--arena-blue)]">
                                            {{ $match->homeEntry?->team_name ?? 'TBD' }} vs {{ $match->awayEntry?->team_name ?? 'TBD' }}
                                        </p>
                                        <p class="mt-2 text-sm text-slate-500">{{ optional($match->scheduled_at)->format('d.m.Y H:i') ?: 'Termin uskoro' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-slate-600">{{ ucfirst($match->status) }}</span>
                                        @if (! is_null($match->home_score) && ! is_null($match->away_score))
                                            <p class="mt-3 text-2xl font-black text-[var(--arena-red)]">{{ $match->home_score }} : {{ $match->away_score }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Još nema unetih mečeva za ovaj događaj.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
