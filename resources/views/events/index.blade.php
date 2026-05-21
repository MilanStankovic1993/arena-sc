@extends('layouts.site', ['title' => 'Dogadjaji | Arena SC'])

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="site-card overflow-hidden bg-[linear-gradient(140deg,rgba(13,59,102,0.98),rgba(215,38,61,0.9))] p-8 text-white">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-blue-100">Dogadjaji</p>
                <h1 class="mt-4 text-5xl font-black leading-none">Turniri i liga za padel pod jednim krovom.</h1>
                <p class="mt-6 text-base leading-8 text-white/75">
                    Ovde će korisnici pratiti prijave, raspored mečeva, rezultate i tabelu. Admin kasnije vodi kompletna pravila i statistiku kroz panel.
                </p>
            </div>

            @if ($featuredEvent)
                <a href="{{ route('events.show', ['event' => $featuredEvent->slug]) }}" class="site-card p-8 transition hover:-translate-y-1">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-[var(--arena-red)]">Izdvojen dogadjaj</p>
                    <h2 class="mt-3 text-3xl font-black text-[var(--arena-blue)]">{{ $featuredEvent->title }}</h2>
                    <p class="mt-4 text-sm leading-8 text-slate-600">{{ $featuredEvent->summary }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <span class="rounded-full bg-blue-50 px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-[var(--arena-blue)]">{{ $featuredEvent->type->label() }}</span>
                        <span class="rounded-full bg-red-50 px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-[var(--arena-red)]">{{ $featuredEvent->status->label() }}</span>
                    </div>
                </a>
            @endif
        </div>

        <div class="mt-10 grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
            @foreach ($events as $event)
                <a href="{{ route('events.show', ['event' => $event->slug]) }}" class="site-card p-6 transition hover:-translate-y-1">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-[var(--arena-red)]">{{ $event->type->label() }}</p>
                            <h2 class="mt-3 text-2xl font-black text-[var(--arena-blue)]">{{ $event->title }}</h2>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-slate-600">{{ $event->status->label() }}</span>
                    </div>

                    <p class="mt-4 text-sm leading-7 text-slate-600">{{ $event->summary }}</p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[1.25rem] bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Ucesnici</p>
                            <p class="mt-2 text-xl font-black text-[var(--arena-blue)]">{{ $event->entries_count }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Mecevi</p>
                            <p class="mt-2 text-xl font-black text-[var(--arena-red)]">{{ $event->matches_count }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endsection
