@extends('group-matches.layout', [
    'title' => $match->sport->name.' · grupni termin',
    'intro' => $match->starts_at->format('d.m.Y. H:i').'–'.$match->ends_at->format('H:i').' · '.$match->duration_minutes.' min',
])

@push('head')
    <script src="{{ asset('js/group-match-join.js') }}?v=3" defer></script>
@endpush

@section('intro-actions')
    <a class="arena-button-secondary" href="{{ route('group-matches.index') }}">Svi grupni termini</a>
@endsection

@section('matches-content')
    @php
        $occupied = [
            'A' => $match->confirmedPlayers->where('side', 'A')->count(),
            'B' => $match->confirmedPlayers->where('side', 'B')->count(),
        ];
        $accepting = $match->status === \App\Models\GroupMatch::Open && $match->closes_at->isFuture() && config('arena.booking.is_open');
        $free = [];
        foreach ($joinableSides as $side) {
            for ($position = $occupied[$side] + 1; $position <= $match->team_size; $position++) {
                $free[$side.'-'.$position] = [$side, $position];
            }
        }
        $side = old('side', request('strana'));
        $position = old('position', request('mesto'));
        $selected = is_string($side) && is_scalar($position) ? ($free[$side.'-'.$position] ?? null) : null;
        if (!$selected && is_string($side) && in_array($side, $joinableSides, true)) {
            $selected = $free[$side.'-'.($occupied[$side] + 1)] ?? null;
        }
        $matchParameters = ['match' => $match, 'poziv' => $teamAccess ? $match->team_invite : null];
    @endphp
    <article class="booking-panel group-match-form-panel">
        <div class="group-match-card-top">
            <h2>{{ array_sum($occupied) }}/{{ $match->capacity() }} potvrđenih igrača</h2>
            <span class="group-match-badge">{{ $match->closes_at->isPast() && in_array($match->status, \App\Models\GroupMatch::Pending, true) ? 'Istekao rok za prijavu' : $match->status_label }}</span>
        </div>
        @if ($accepting && $free)
            <p class="group-match-court-tip"><strong>Prijavi se za igru</strong> · Izaberi plus na slobodnom mestu.</p>
        @endif
        @include('group-matches.court')
        @if ($match->confirmedPlayers->isNotEmpty() && $match->starts_at->isFuture()
            && (in_array($match->status, \App\Models\GroupMatch::Pending, true) || $match->hasActiveReservation()))
            <section class="group-match-roster" aria-labelledby="group-match-roster-title">
                <h3 id="group-match-roster-title">Ko igra</h3>
                <div class="group-match-roster-teams">
                    @foreach (['A', 'B'] as $side)
                        <div class="group-match-roster-team">
                            <h4>Ekipa {{ $side }}</h4>
                            <ul>
                                @forelse ($match->confirmedPlayers->where('side', $side) as $player)
                                    <li><span>{{ $player->name }}</span><strong>{{ $player->self_rating }}/10</strong></li>
                                @empty
                                    <li>Još nema potvrđenih igrača.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
        @if ($match->hasActiveReservation())
            <p>{{ $match->reservation->court?->name }} · <strong>{{ number_format((float) $match->reservation->total_price, 2, ',', '.') }} RSD za ceo teren.</strong> Ekipa se dogovara kako deli troškove.</p>
        @elseif (in_array($match->status, \App\Models\GroupMatch::Pending, true) && $match->closes_at->isFuture())
            <p><strong>Do {{ number_format((float) $match->quoted_price, 2, ',', '.') }} RSD za ceo teren.</strong> Ekipa se dogovara kako deli troškove.</p>
            <p class="group-match-help">Prijave do {{ $match->closes_at->format('H:i') }}. Potvrdu rezervacije terena šaljemo svim prijavljenima mejlom.</p>
        @endif
        @if ($match->closing_note)<p>{{ $match->closing_note }}</p>@endif
    </article>

    @if ($accepting && $free)
        <dialog id="prijava" class="group-match-dialog" aria-labelledby="group-match-join-title" data-group-match-dialog @if ($selected) open @endif>
            <div class="group-match-dialog-header">
                <div>
                    <h2 id="group-match-join-title">Prijavi se za igru</h2>
                    <p class="group-match-help" data-selected-team>Ekipa {{ $selected[0] ?? '' }}</p>
                    <p><strong>Do {{ number_format((float) $match->quoted_price, 2, ',', '.') }} RSD za ceo teren.</strong> Ekipa se dogovara kako deli troškove.</p>
                </div>
                <a class="group-match-dialog-close" data-close-group-dialog href="{{ route('group-matches.show', $matchParameters) }}" aria-label="Zatvori prijavu">×</a>
            </div>
            @if ($errors->any() && $selected)
                <div class="group-match-notice group-match-error" role="alert">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="post" action="{{ route('group-matches.join', $match) }}" class="group-match-form">
                @csrf
                @if ($teamAccess)<input type="hidden" name="invite" value="{{ $match->team_invite }}">@endif
                <input type="hidden" name="side" value="{{ $selected[0] ?? '' }}">
                <input type="hidden" name="position" value="{{ $selected[1] ?? '' }}">
                <input type="hidden" name="group_questionnaire" value="1" disabled>
                <div class="group-match-wizard-progress" data-wizard-progress aria-live="polite" hidden>
                    <strong data-wizard-stage></strong>
                    <span data-wizard-caption></span>
                </div>
                <div class="group-match-wizard-roster" data-wizard-roster>
                    @include('group-matches.fields')
                    @if (count($free) > 1)
                        <details class="group-match-guest-details" @if (old('guests') && $errors->any()) open @endif>
                            <summary>Dodaj i ekipu</summary>
                            <p class="group-match-help">Otvori slobodno mesto i unesi ime i mejl saigrača.</p>
                            @foreach ($free as $key => [$side, $position])
                                <details class="group-match-roster-slot" name="group-match-roster-slot" @if ($selected === [$side, $position]) hidden @endif>
                                    <summary>
                                        <span>Ekipa {{ $side }} · mesto {{ $position }}</span>
                                        <span class="group-match-help" data-wizard-guest-label>{{ old('guests.'.$key.'.name') ?: 'Slobodno' }}</span>
                                    </summary>
                                    <fieldset class="group-match-fields" data-guest-key="{{ $key }}" data-guest-side="{{ $side }}" data-guest-position="{{ $position }}" @if ($selected === [$side, $position]) disabled @endif>
                                        <input type="hidden" name="guests[{{ $key }}][side]" value="{{ $side }}">
                                        <label>Ime i prezime igrača
                                            <input name="guests[{{ $key }}][name]" data-wizard-guest-name maxlength="255" value="{{ old('guests.'.$key.'.name') }}">
                                        </label>
                                        <label>Mejl igrača
                                            <input type="email" name="guests[{{ $key }}][email]" data-wizard-guest-email maxlength="255" value="{{ old('guests.'.$key.'.email') }}">
                                        </label>
                                    </fieldset>
                                </details>
                            @endforeach
                        </details>
                    @endif
                </div>
                @if ($account === null)
                    <div data-wizard-player="self">
                        @include('group-matches.questionnaire', ['questionSport' => $match->sport->slug])
                    </div>
                @endif
                @if (count($free) > 1)
                    @foreach (array_keys($free) as $key)
                        <fieldset data-wizard-player="{{ $key }}" disabled hidden>
                            @include('group-matches.questionnaire', [
                                'questionSport' => $match->sport->slug,
                                'questionnaireInput' => 'guests['.$key.']',
                                'questionnaireOld' => 'guests.'.$key,
                            ])
                        </fieldset>
                    @endforeach
                @endif
                <div data-wizard-review hidden>
                    <h3 tabindex="-1">Pregled prijave</h3>
                    <ul class="group-match-wizard-summary" data-wizard-summary></ul>
                    <p class="group-match-help">Svaki igrač dobija mejl za potvrdu svog mesta.</p>
                </div>
                <p class="group-match-help" data-wizard-consent>Potvrdom prihvataš prikazani termin i cenu. Ime, prezime i ocena potvrđenih igrača vide se na oglasu. Prijavu koja proširi raspon ocena preko tri pregleda Arena pre dodele mesta.</p>
                <div class="group-match-wizard-actions">
                    <button class="arena-button-secondary" type="button" data-wizard-back hidden>Nazad</button>
                    <button class="arena-button-primary" type="button" data-wizard-next hidden>Idi dalje</button>
                    <button class="arena-button-primary" type="submit" data-wizard-submit>Pošalji mejl za potvrdu</button>
                </div>
            </form>
        </dialog>
    @endif
@endsection
