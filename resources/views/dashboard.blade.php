<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="eyebrow-chip">Moj nalog</span>
            <h2 class="section-title mt-4">Pregled rezervacija.</h2>
        </div>
    </x-slot>

    <div class="grid gap-8 lg:grid-cols-[0.84fr_1.16fr]">
            <div class="space-y-6">
                <div class="account-card-dark">
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
                        <a href="{{ route('booking.index') }}" class="arena-button-primary">Nova rezervacija</a>
                    </div>
                </div>

                <div class="account-card">
                    <h3 class="account-section-title">Članarina</h3>
                    <div class="mt-5 space-y-4">
                        @forelse ($memberships as $membership)
                            <div class="rounded-[1.4rem] border border-[rgba(15,42,31,0.1)] bg-white/75 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-[color:var(--arena-forest)]">{{ $membership->membershipPlan->name }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $membership->membershipPlan->sport?->name ?? 'Svi sportovi' }}</p>
                                    </div>
                                    <span class="info-chip">Do {{ $membership->ends_at->format('d.m.Y') }}</span>
                                </div>
                                <p class="mt-3 text-sm text-slate-600">
                                    Limit: {{ $membership->membershipPlan->reservation_limit }} termina ukupno.
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Trenutno nemas aktivnu članarinu.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="account-card">
                <h3 class="account-section-title">Istorija rezervacija</h3>
                <div class="mt-5 space-y-4">
                    @forelse ($reservations as $reservation)
                        <div class="rounded-[1.5rem] border border-[rgba(15,42,31,0.1)] bg-white/70 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-[color:var(--arena-forest)]">{{ $reservation->court->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $reservation->sport->name }} | {{ $reservation->starts_at->format('d.m.Y H:i') }}</p>
                                    @if ($reservation->user_id !== auth()->id())
                                        <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">Rezervisao: {{ $reservation->user?->name }}</p>
                                    @endif
                                </div>
                                <span class="info-chip">{{ $reservation->status->label() }}</span>
                            </div>
                            <p class="mt-3 text-sm text-slate-600">Ukupna cena: {{ number_format($reservation->total_price, 0, ',', '.') }} RSD</p>
                            @if ($reservation->user_id === auth()->id() && $reservation->status === \App\Enums\ReservationStatus::Reserved && $reservation->starts_at->isFuture())
                                <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" class="mt-4">
                                    @csrf
                                    <button type="submit" class="arena-button-secondary-light">Otkazi termin</button>
                                </form>
                            @elseif ($reservation->status === \App\Enums\ReservationStatus::Cancelled && $reservation->cancellation_reason)
                                <p class="mt-3 text-sm text-slate-500">Razlog: {{ $reservation->cancellation_reason }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Jos uvek nemas poslate rezervacije.</p>
                    @endforelse
                </div>
            </div>
    </div>
</x-app-layout>
