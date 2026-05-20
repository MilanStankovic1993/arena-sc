<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Oprema | Arena SC</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-950 text-white">
        <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-300">Katalog opreme</p>
                    <h1 class="mt-3 text-4xl font-black">Iznajmljivanje i prodaja opreme</h1>
                </div>
                <a href="{{ route('home') }}" class="rounded-full border border-white/20 px-5 py-3 text-sm font-semibold">Pocetna</a>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($equipment as $item)
                    <article class="rounded-[2rem] bg-white/10 p-6 ring-1 ring-white/10">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-300">{{ $item->sport?->name ?? 'Opste' }}</p>
                        <h2 class="mt-3 text-2xl font-black">{{ $item->name }}</h2>
                        <p class="mt-4 text-sm leading-7 text-slate-300">{{ $item->short_description }}</p>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-white/10 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Rent</p>
                                <p class="mt-2 font-bold">{{ number_format($item->rental_price, 0, ',', '.') }} RSD</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Prodaja</p>
                                <p class="mt-2 font-bold">{{ number_format($item->sale_price, 0, ',', '.') }} RSD</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </main>
    </body>
</html>
