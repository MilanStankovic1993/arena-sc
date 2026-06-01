<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-black tracking-tight text-[color:var(--arena-forest)]">Moj nalog</h2>
            <p class="text-sm text-slate-500">Prati istoriju rezervacija i brzo se vrati na pregled termina.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
            <div class="space-y-6">
                <div class="rounded-[2rem] bg-[linear-gradient(145deg,rgba(8,38,28,0.98),rgba(18,63,48,0.93))] p-6 text-white shadow-sm">
                    <p class="text-sm uppercase tracking-[0.3em] text-[color:var(--arena-sand)]">Moj profil</p>
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
                    <div class="mt-6">
                        <a href="{{ route('sports.index') }}" class="inline-flex rounded-full bg-white px-5 py-3 text-sm font-bold uppercase tracking-[0.18em] text-[color:var(--arena-forest)]">Nova rezervacija</a>
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-[color:var(--arena-border)]">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                <h3 class="text-lg font-bold text-[color:var(--arena-forest)]">Istorija zahteva</h3>
                <div class="mt-5 space-y-4">
                    @forelse ($reservations as $reservation)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-[color:var(--arena-forest)]">{{ $reservation->court->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $reservation->sport->name }} | {{ $reservation->starts_at->format('d.m.Y H:i') }}</p>
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
</x-app-layout>
