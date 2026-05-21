@extends('layouts.site', ['title' => 'Oprema | Arena SC'])

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="site-pill">Katalog opreme</span>
                <h1 class="mt-4 text-5xl font-black text-[var(--arena-blue)]">Oprema za prodaju i iznajmljivanje.</h1>
                <p class="mt-4 max-w-2xl text-base leading-8 text-slate-600">
                    Na ovoj stranici korisnik vidi šta može da kupi, a šta može da doda uz rezervaciju termina kao dodatnu opremu.
                </p>
            </div>
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($equipment as $item)
                <article class="site-card overflow-hidden p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-[var(--arena-red)]">{{ $item->sport?->name ?? 'Oprema' }}</p>
                            <h2 class="mt-3 text-2xl font-black text-[var(--arena-blue)]">{{ $item->name }}</h2>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-slate-600">{{ $item->stock_quantity }} kom</span>
                    </div>

                    <p class="mt-4 text-sm leading-7 text-slate-600">{{ $item->short_description }}</p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Iznajmljivanje</p>
                            <p class="mt-2 text-xl font-black text-[var(--arena-blue)]">{{ number_format($item->rental_price, 0, ',', '.') }} RSD</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Prodaja</p>
                            <p class="mt-2 text-xl font-black text-[var(--arena-red)]">{{ number_format($item->sale_price, 0, ',', '.') }} RSD</p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2">
                        @if ($item->is_rentable)
                            <span class="rounded-full bg-blue-50 px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-[var(--arena-blue)]">Moze uz termin</span>
                        @endif
                        @if ($item->is_sellable)
                            <span class="rounded-full bg-red-50 px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-[var(--arena-red)]">Dostupno za prodaju</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endsection
