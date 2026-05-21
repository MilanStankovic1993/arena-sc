@extends('layouts.site', ['title' => $court->name . ' | Arena SC'])

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[0.92fr_1.08fr]">
            <div class="space-y-6">
                <div class="site-card overflow-hidden bg-[linear-gradient(145deg,rgba(13,59,102,0.96),rgba(215,38,61,0.82))] p-8 text-white">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-blue-100">{{ $court->sport->name }}</p>
                    <h1 class="mt-3 text-5xl font-black">{{ $court->name }}</h1>
                    <p class="mt-5 text-base leading-8 text-white/75">{{ $court->description }}</p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-[1.5rem] bg-white/10 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-100">Lokacija</p>
                            <p class="mt-2 font-semibold">{{ $court->location }}</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-white/10 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-100">Podloga</p>
                            <p class="mt-2 font-semibold">{{ $court->surface }}</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-white/10 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-blue-100">Osnovna cena</p>
                            <p class="mt-2 font-semibold">{{ number_format($court->base_price, 0, ',', '.') }} RSD</p>
                        </div>
                    </div>
                </div>

                <div class="site-card p-6">
                    <p class="text-sm font-bold uppercase tracking-[0.3em] text-[var(--arena-blue-soft)]">Cene po pravilima</p>
                    <div class="mt-5 space-y-4">
                        @foreach ($court->pricingRules as $rule)
                            <div class="rounded-[1.5rem] border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-bold text-[var(--arena-blue)]">{{ $rule->name }}</p>
                                        <p class="text-sm text-slate-500">{{ substr($rule->start_time, 0, 5) }} - {{ substr($rule->end_time, 0, 5) }}</p>
                                    </div>
                                    <span class="text-lg font-black text-[var(--arena-red)]">{{ number_format($rule->price, 0, ',', '.') }} RSD</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="site-card p-6">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.3em] text-[var(--arena-red)]">Pregled po danu</p>
                            <h2 class="mt-3 text-3xl font-black text-[var(--arena-blue)]">Slobodni termini i cena pre rezervacije.</h2>
                        </div>
                        <span class="rounded-full bg-slate-100 px-4 py-2 text-xs font-bold uppercase tracking-[0.22em] text-slate-600">
                            {{ auth()->check() ? 'Ulogovan korisnik moze da rezervise' : 'Pregled je javan, rezervacija zahteva nalog' }}
                        </span>
                    </div>

                    <form method="GET" class="mt-6 grid gap-4 md:grid-cols-3">
                        <input type="hidden" name="slot" value="">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Dan</label>
                            <input type="date" name="date" value="{{ $selectedDay->format('Y-m-d') }}" min="{{ now()->format('Y-m-d') }}" class="mt-2 w-full rounded-2xl border-slate-300">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Trajanje</label>
                            <select name="duration" class="mt-2 w-full rounded-2xl border-slate-300">
                                <option value="60" @selected($selectedDuration === 60)>1h</option>
                                <option value="90" @selected($selectedDuration === 90)>1,5h</option>
                                <option value="120" @selected($selectedDuration === 120)>2h</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button class="arena-button-primary w-full">Prikazi termine</button>
                        </div>
                    </form>

                    <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-[0.22em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-4">Termin</th>
                                    <th class="px-4 py-4">Cena</th>
                                    <th class="px-4 py-4">Status</th>
                                    <th class="px-4 py-4 text-right">Akcija</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach ($dailySlots as $slot)
                                    <tr>
                                        <td class="px-4 py-4 font-semibold text-[var(--arena-blue)]">{{ $slot['starts_at']->format('H:i') }} - {{ $slot['ends_at']->format('H:i') }}</td>
                                        <td class="px-4 py-4">{{ number_format($slot['price'], 0, ',', '.') }} RSD</td>
                                        <td class="px-4 py-4">
                                            @if ($slot['available'])
                                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Slobodno</span>
                                            @else
                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Zauzeto</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            @if ($slot['available'])
                                                <a href="{{ route('courts.show', ['court' => $court->slug, 'date' => $selectedDay->format('Y-m-d'), 'duration' => $selectedDuration, 'slot' => $slot['key']]) }}" class="text-sm font-bold uppercase tracking-[0.18em] text-[var(--arena-red)]">Odaberi</a>
                                            @else
                                                <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Nedostupno</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($selectedSlotData)
                    <div class="site-card p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold uppercase tracking-[0.3em] text-[var(--arena-red)]">Izabrani termin</p>
                                <h3 class="mt-3 text-3xl font-black text-[var(--arena-blue)]">{{ $selectedSlotData['starts_at']->format('d.m.Y') }} · {{ $selectedSlotData['starts_at']->format('H:i') }} - {{ $selectedSlotData['ends_at']->format('H:i') }}</h3>
                                <p class="mt-3 text-sm leading-7 text-slate-600">Cena terena za ovaj slot iznosi {{ number_format($selectedSlotData['price'], 0, ',', '.') }} RSD. Opremu možeš dodati opciono.</p>
                            </div>
                            <span class="rounded-full bg-blue-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-[var(--arena-blue)]">{{ $selectedDuration / 60 == 1.5 ? '1,5h' : rtrim(rtrim(number_format($selectedDuration / 60, 1, ',', ''), '0'), ',') . 'h' }}</span>
                        </div>

                        @if (! $selectedSlotData['available'])
                            <div class="mt-6 rounded-[1.5rem] border border-red-200 bg-red-50 px-4 py-4 text-sm font-semibold text-red-700">
                                Ovaj termin je u međuvremenu zauzet. Izaberi drugi slobodan slot iz tabele iznad.
                            </div>
                        @elseif (! auth()->check())
                            <div class="mt-6 rounded-[1.5rem] border border-blue-200 bg-blue-50 p-5">
                                <p class="text-sm leading-7 text-slate-700">Pregled termina je dostupan svima, ali rezervaciju može da potvrdi samo registrovan korisnik.</p>
                                <div class="mt-4 flex flex-wrap gap-3">
                                    <a href="{{ route('register') }}" class="arena-button-primary">Registruj se</a>
                                    <a href="{{ route('login') }}" class="arena-button-secondary">Prijavi se</a>
                                </div>
                            </div>
                        @else
                            <form method="POST" action="{{ route('reservations.store') }}" class="mt-6 space-y-5">
                                @csrf
                                <input type="hidden" name="court_id" value="{{ $court->id }}">
                                <input type="hidden" name="starts_at" value="{{ $selectedSlotData['starts_at']->format('Y-m-d H:i:s') }}">
                                <input type="hidden" name="duration_minutes" value="{{ $selectedDuration }}">

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="text-sm font-semibold text-slate-700">Broj igraca</label>
                                        <input type="number" min="1" max="20" name="players_count" value="{{ old('players_count') }}" class="mt-2 w-full rounded-2xl border-slate-300">
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-slate-700">Cena termina</label>
                                        <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold text-[var(--arena-blue)]">
                                            {{ number_format($selectedSlotData['price'], 0, ',', '.') }} RSD
                                        </div>
                                    </div>
                                </div>

                                @if ($equipment->isNotEmpty())
                                    <div class="rounded-[1.5rem] bg-slate-50 p-4">
                                        <div class="flex items-center justify-between gap-4">
                                            <div>
                                                <p class="text-sm font-bold uppercase tracking-[0.26em] text-[var(--arena-blue-soft)]">Opciona oprema</p>
                                                <p class="mt-2 text-sm text-slate-600">Korisnik može da doda opremu, ali nije obavezno.</p>
                                            </div>
                                        </div>
                                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                                            @foreach ($equipment as $index => $item)
                                                <div class="rounded-[1.25rem] border border-slate-200 bg-white p-4">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div>
                                                            <p class="font-bold text-[var(--arena-blue)]">{{ $item->name }}</p>
                                                            <p class="mt-1 text-sm text-slate-500">{{ $item->short_description }}</p>
                                                        </div>
                                                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-[var(--arena-red)]">{{ number_format($item->rental_price, 0, ',', '.') }} RSD</span>
                                                    </div>
                                                    <input type="hidden" name="equipment[{{ $index }}][equipment_id]" value="{{ $item->id }}">
                                                    <label class="mt-4 block text-sm font-semibold text-slate-700">Kolicina</label>
                                                    <input type="number" min="0" max="10" name="equipment[{{ $index }}][quantity]" value="{{ old("equipment.$index.quantity", 0) }}" class="mt-2 w-full rounded-2xl border-slate-300">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div>
                                    <label class="text-sm font-semibold text-slate-700">Napomena</label>
                                    <textarea name="customer_note" rows="4" class="mt-2 w-full rounded-2xl border-slate-300">{{ old('customer_note') }}</textarea>
                                </div>

                                <button class="arena-button-primary">Posalji zahtev za rezervaciju</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
