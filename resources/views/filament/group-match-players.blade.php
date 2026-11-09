<div class="space-y-4">
    <p>{{ $match->sport->name }} · {{ $match->starts_at->format('d.m.Y. H:i') }}–{{ $match->ends_at->format('H:i') }}</p>
    @if ($match->hasActiveReservation())
        <p>{{ number_format((float) $match->reservation->total_price, 2, ',', '.') }} RSD za ceo teren</p>
    @else
        <p>Prikazana cena do {{ number_format((float) $match->quoted_price, 2, ',', '.') }} RSD za ceo teren</p>
    @endif
    @if ($courts !== null)
        @if ($match->needsLevelReview())
            <p><strong>Razlog pregleda</strong> Razlika između najviše i najniže samoprocene veća je od {{ \App\Models\GroupMatch::AutomaticRatingSpread }}.</p>
        @else
            <p><strong>Razlog pregleda</strong> Potrebna je odluka o raspodeli slobodnih terena.</p>
        @endif
        <p><strong>Slobodni tereni po prikazanoj ceni</strong> {{ $courts->isEmpty() ? 'Trenutno nema slobodnog terena.' : $courts->implode(', ') }}</p>
    @endif
    @foreach (['A', 'B'] as $side)
        <div>
            <p class="font-semibold">Ekipa {{ $side }}</p>
            <ul>
                @forelse ($match->confirmedPlayers->where('side', $side) as $player)
                    <li>{{ $player->name }}{{ $player->organizer ? ' (organizator)' : '' }} · procena {{ $player->level }}/10 · ocena za termin {{ $player->self_rating }}/10</li>
                @empty
                    <li>Još nema potvrđenih igrača.</li>
                @endforelse
            </ul>
        </div>
    @endforeach
    @if ($match->playersAwaitingLevelReview->isNotEmpty())
        <div>
            <p class="font-semibold">Prijave za pregled nivoa igre</p>
            <ul>
                @foreach ($match->playersAwaitingLevelReview as $player)
                    <li>{{ $player->name }} · ekipa {{ $player->side }} · samoprocena {{ $player->self_rating }}/10</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
