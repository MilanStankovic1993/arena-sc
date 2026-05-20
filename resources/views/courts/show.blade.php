<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $court->name }} | Arena SC</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f6f6f3] text-slate-900">
        <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <a href="{{ route('sports.index') }}" class="text-sm font-semibold text-slate-500">← Nazad na sportove</a>

            <section class="mt-6 grid gap-8 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="rounded-[2.5rem] bg-slate-950 p-8 text-white">
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-300">{{ $court->sport->name }}</p>
                    <h1 class="mt-4 text-5xl font-black">{{ $court->name }}</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">{{ $court->description }}</p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Lokacija</p>
                            <p class="mt-2 font-semibold">{{ $court->location }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Podloga</p>
                            <p class="mt-2 font-semibold">{{ $court->surface }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Pocetna cena</p>
                            <p class="mt-2 font-semibold">{{ number_format($court->base_price, 0, ',', '.') }} RSD</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2.5rem] bg-white p-8 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-2xl font-black">Cene i pravila termina</h2>
                    <div class="mt-6 space-y-4">
                        @foreach ($court->pricingRules as $rule)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $rule->name }}</p>
                                        <p class="text-sm text-slate-500">{{ $rule->start_time }} - {{ $rule->end_time }}</p>
                                    </div>
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-bold text-amber-700">{{ number_format($rule->price, 0, ',', '.') }} RSD</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($equipment->isNotEmpty())
                        <h3 class="mt-8 text-lg font-bold text-slate-900">Dostupna oprema za iznajmljivanje</h3>
                        <div class="mt-4 space-y-3">
                            @foreach ($equipment as $item)
                                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                                    <div>
                                        <p class="font-semibold">{{ $item->name }}</p>
                                        <p class="text-sm text-slate-500">{{ $item->short_description }}</p>
                                    </div>
                                    <span class="font-bold">{{ number_format($item->rental_price, 0, ',', '.') }} RSD</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-8">
                        <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="inline-flex rounded-full bg-slate-950 px-6 py-3 text-sm font-semibold text-white">
                            {{ auth()->check() ? 'Rezervisi iz naloga' : 'Kreiraj nalog za rezervaciju' }}
                        </a>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
