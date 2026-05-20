<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black tracking-tight text-slate-900">Moje rezervacije</h2>
                <p class="text-sm text-slate-500">Kreiraj novi zahtev i prati status svojih termina.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1.15fr_0.85fr] lg:px-8">
            <div class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                <h3 class="text-lg font-bold text-slate-900">Zahtev za novi termin</h3>
                <form method="POST" action="{{ route('reservations.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Sport</label>
                            <select name="sport_id" class="mt-2 w-full rounded-2xl border-slate-300">
                                @foreach ($sports as $sport)
                                    <option value="{{ $sport->id }}" @selected(old('sport_id') == $sport->id)>{{ $sport->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-slate-700">Teren</label>
                            <select name="court_id" class="mt-2 w-full rounded-2xl border-slate-300">
                                @foreach ($courts as $court)
                                    <option value="{{ $court->id }}" @selected(old('court_id') == $court->id)>{{ $court->name }} / {{ $court->sport->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-slate-700">Pocetak termina</label>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="mt-2 w-full rounded-2xl border-slate-300">
                            <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-slate-700">Trajanje</label>
                            <select name="duration_minutes" class="mt-2 w-full rounded-2xl border-slate-300">
                                <option value="60">60 minuta</option>
                                <option value="90">90 minuta</option>
                                <option value="120">120 minuta</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-slate-700">Broj igraca</label>
                            <input type="number" min="1" max="20" name="players_count" value="{{ old('players_count') }}" class="mt-2 w-full rounded-2xl border-slate-300">
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-800">Dodatna oprema</p>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            @foreach ($equipment as $index => $item)
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $item->name }}</p>
                                            <p class="text-sm text-slate-500">{{ $item->sport?->name }}</p>
                                        </div>
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">{{ number_format($item->rental_price, 0, ',', '.') }} RSD</span>
                                    </div>

                                    <input type="hidden" name="equipment[{{ $index }}][equipment_id]" value="{{ $item->id }}">
                                    <label class="mt-4 block text-sm text-slate-600">Kolicina</label>
                                    <input type="number" min="0" max="10" name="equipment[{{ $index }}][quantity]" value="{{ old("equipment.$index.quantity", 0) }}" class="mt-2 w-full rounded-2xl border-slate-300">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Napomena</label>
                        <textarea name="customer_note" rows="4" class="mt-2 w-full rounded-2xl border-slate-300">{{ old('customer_note') }}</textarea>
                    </div>

                    <button class="inline-flex rounded-full bg-slate-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Posalji zahtev za termin
                    </button>
                </form>
            </div>

            <div class="space-y-6">
                <div class="rounded-[2rem] bg-slate-950 p-6 text-white shadow-sm">
                    <p class="text-sm uppercase tracking-[0.3em] text-amber-300">Moj nalog</p>
                    <h3 class="mt-3 text-3xl font-black">{{ auth()->user()->name }}</h3>
                    <div class="mt-6 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Rezervacije</p>
                            <p class="mt-2 text-2xl font-bold">{{ auth()->user()->total_reservations }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Otkazane</p>
                            <p class="mt-2 text-2xl font-bold">{{ auth()->user()->cancelled_reservations }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Poslednji termin</p>
                            <p class="mt-2 text-sm font-semibold">{{ optional(auth()->user()->last_reservation_at)->format('d.m.Y H:i') ?? 'Nema jos termina' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">Istorija zahteva</h3>
                    <div class="mt-5 space-y-4">
                        @forelse ($reservations as $reservation)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $reservation->court->name }}</p>
                                        <p class="text-sm text-slate-500">{{ $reservation->sport->name }} • {{ $reservation->starts_at->format('d.m.Y H:i') }}</p>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-slate-700">{{ $reservation->status->label() }}</span>
                                </div>
                                <p class="mt-3 text-sm text-slate-600">Ukupna cena: {{ number_format($reservation->total_price, 0, ',', '.') }} RSD</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Jos uvek nemas poslate rezervacije.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
