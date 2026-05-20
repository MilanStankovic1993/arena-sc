<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sportovi | Arena SC</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-900">
        <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-600">Sportski program</p>
                    <h1 class="mt-3 text-4xl font-black">Sportovi i dostupni tereni</h1>
                </div>
                <a href="{{ route('home') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold">Nazad na pocetnu</a>
            </div>

            <div class="mt-10 space-y-8">
                @foreach ($sports as $sport)
                    <section class="rounded-[2rem] bg-white p-8 shadow-sm ring-1 ring-slate-200">
                        <div class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
                            <div>
                                <h2 class="text-3xl font-black">{{ $sport->name }}</h2>
                                <p class="mt-4 leading-8 text-slate-600">{{ $sport->description }}</p>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach ($sport->courts as $court)
                                    <a href="{{ route('courts.show', $court) }}" class="rounded-[1.5rem] bg-slate-100 p-5 transition hover:bg-slate-950 hover:text-white">
                                        <p class="text-lg font-bold">{{ $court->name }}</p>
                                        <p class="mt-2 text-sm opacity-80">{{ $court->location }}</p>
                                        <p class="mt-4 text-sm font-semibold">Od {{ number_format($court->base_price, 0, ',', '.') }} RSD</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </main>
    </body>
</html>
