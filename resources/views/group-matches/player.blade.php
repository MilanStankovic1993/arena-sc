@extends('group-matches.layout', [
    'title' => 'Tvoja prijava',
    'intro' => $player->groupMatch->sport->name.' · '.$player->groupMatch->starts_at->format('d.m.Y. H:i'),
])

@section('matches-content')
    @php
        $match = $player->groupMatch;
        $awaitingReview = $player->level_review_status === \App\Models\GroupMatchPlayer::LevelReviewPending;
        $declined = $player->level_review_status === \App\Models\GroupMatchPlayer::LevelReviewDeclined;
        $pending = $match->closes_at->isFuture() && in_array($match->status, \App\Models\GroupMatch::Pending, true);
        $seatAvailable = $match->confirmedPlayers->where('side', $player->side)->count() < $match->team_size;
        $canConfirm = $pending && !$player->confirmed_at && !$awaitingReview && !$declined && $seatAvailable
            && $match->status === ($player->organizer ? \App\Models\GroupMatch::Draft : \App\Models\GroupMatch::Open)
            && config('arena.booking.is_open') && $match->sport->is_active && $match->sport->supports_online_booking;
        $canWithdraw = $pending && ($player->confirmed_at || $awaitingReview);
    @endphp
    <article class="booking-panel group-match-form-panel">
        <h2>{{ $player->name }}</h2>
        <p>
            Ekipa {{ $player->side }}
            @unless ($player->needsQuestionnaire())
                · prijavljena ocena {{ $player->self_rating }}/10
            @endunless
        </p>
        @if ($player->confirmed_at && $match->hasActiveReservation())
            <p class="group-match-notice"><strong>{{ number_format((float) $match->reservation->total_price, 2, ',', '.') }} RSD za ceo teren.</strong> Ekipa se dogovara kako deli troškove.</p>
        @elseif ($canConfirm || $canWithdraw)
            <p class="group-match-notice"><strong>Do {{ number_format((float) $match->quoted_price, 2, ',', '.') }} RSD za ceo teren.</strong> Ekipa se dogovara kako deli troškove.</p>
        @endif
        @if ($player->confirmed_at && $match->status === \App\Models\GroupMatch::Booked && !$match->hasActiveReservation())
            <p class="group-match-notice">Termin više nije aktivan. Status termina · {{ $match->status_label }}.</p>
        @elseif ($player->confirmed_at)
            <p class="group-match-notice">Tvoje mesto je potvrđeno. Status termina · {{ $match->status_label }}.</p>
            @if ($match->status === \App\Models\GroupMatch::Open && $player->organizer && $match->team_invite)
                <label class="group-match-invite">Pošalji saigračima link za prijavu u tvoju ekipu
                    <input type="url" readonly value="{{ route('group-matches.show', ['match' => $match, 'poziv' => $match->team_invite]) }}">
                </label>
            @endif
            <a class="arena-button-secondary" href="{{ route('group-matches.show', $match) }}">Pogledaj oglas</a>
        @elseif ($declined)
            <p class="group-match-notice">Arena nije odobrila tvoju prijavu za ovaj termin.</p>
        @elseif ($awaitingReview)
            <p class="group-match-notice">
                @if ($match->status === \App\Models\GroupMatch::Open && $pending)
                    Arena pregleda tvoju prijavu zbog razlike u oceni. Mesto se dodeljuje nakon odobrenja, ako je i dalje slobodno.
                @else
                    Prijava je zatvorena i tvoje mesto nije potvrđeno.
                @endif
            </p>
        @elseif (!$canConfirm)
            <p class="group-match-notice">Tvoje mesto trenutno nije dostupno za potvrdu.</p>
        @endif
        @if ($canConfirm || $canWithdraw)
            <form method="post" action="{{ $player->signedUrl() }}" class="group-match-form">
                @csrf
                @if ($canConfirm)
                    @if ($player->needsQuestionnaire())
                        @include('group-matches.questionnaire', ['questionSport' => $match->sport->slug])
                    @endif
                    <p>Potvrdom prihvataš izabrano vreme i prikazanu cenu terena. Ime, prezime i ocena potvrđenih igrača vide se na oglasu.</p>
                    <button class="arena-button-primary" name="action" value="confirm" type="submit">Potvrdi moje mesto</button>
                @else
                    <button class="arena-button-secondary" name="action" value="leave" type="submit">{{ $player->organizer ? 'Povuci oglas' : 'Povuci moju prijavu' }}</button>
                @endif
            </form>
        @endif
    </article>
@endsection
