@extends('layouts.site', ['title' => 'Tereni i sportovi | Arena SC'])

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="site-pill">Tereni i sportovi</span>
                <h1 class="mt-4 text-5xl font-black text-[var(--arena-blue)]">Pregled sportova i svih raspoloživih terena.</h1>
                <p class="mt-4 max-w-2xl text-base leading-8 text-slate-600">
                    Izaberi sport, otvori teren i vidi raspored po danu sa cenom i slobodnim terminima za 1h, 1.5h ili 2h.
                </p>
            </div>
        </div>

        <div class="mt-10 space-y-8">
            @foreach ($sports as $sport)
                <section class="site-card p-8">
                    <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-[var(--arena-red)]">{{ $sport->name }}</p>
                            <h2 class="mt-3 text-3xl font-black text-[var(--arena-blue)]">{{ $sport->name }} program</h2>
                            <p class="mt-4 text-sm leading-8 text-slate-600">{{ $sport->description }}</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($sport->courts as $court)
                                <a href="{{ route('courts.show', ['court' => $court->slug]) }}" class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5 transition hover:border-[var(--arena-blue)] hover:bg-white hover:shadow-lg">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-xl font-black text-[var(--arena-blue)]">{{ $court->name }}</h3>
                                            <p class="mt-2 text-sm text-slate-500">{{ $court->location }}</p>
                                        </div>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">{{ $court->surface }}</span>
                                    </div>
                                    <p class="mt-4 text-sm leading-7 text-slate-600">{{ $court->description }}</p>
                                    <div class="mt-5 flex items-center justify-between">
                                        <span class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Pocetna cena</span>
                                        <span class="text-lg font-black text-[var(--arena-red)]">{{ number_format($court->base_price, 0, ',', '.') }} RSD</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    </section>
@endsection
