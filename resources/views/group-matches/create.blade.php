@extends('group-matches.layout', [
    'title' => 'Kreiraj novi grupni termin',
    'intro' => 'Izaberi sport, datum i vreme. Kad potvrdiš svoju prijavu iz mejla, oglas će se pojaviti na listi.',
])

@section('intro-actions')
    <a class="arena-button-secondary" href="{{ route('group-matches.index') }}">Svi grupni termini</a>
@endsection

@section('matches-content')
    <div class="booking-panel group-match-form-panel">
        <h2>Izaberi termin</h2>
        <form method="get" action="{{ route('group-matches.create') }}" class="group-match-form">
            <div class="group-match-fields">
                <label>Sport
                    <select name="sport_id" required>
                        <option value="">Izaberi sport</option>
                        @foreach ($sports as $option)
                            <option value="{{ $option->id }}" @selected((string) old('sport_id', $slot['sport_id'] ?? '') === (string) $option->id || (!old('sport_id') && !$slot && request('sport') === $option->slug))>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Datum i vreme
                    <input type="datetime-local" name="starts_at" required step="1800" value="{{ old('starts_at', $slot['starts_at'] ?? '') }}">
                </label>
                <label>Trajanje
                    <select name="duration_minutes" required>
                        @foreach ([60, 90, 120] as $duration)
                            <option value="{{ $duration }}" @selected((int) old('duration_minutes', $slot['duration_minutes'] ?? 60) === $duration)>{{ $duration }} min</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <button class="arena-button-secondary" type="submit">Proveri dostupnost i cenu</button>
        </form>
    </div>

    @if ($slot)
        <div class="booking-panel group-match-form-panel">
            <h2>Tvoja prijava</h2>
            <p class="group-match-notice">{{ $sport->name }} · {{ \Carbon\Carbon::parse($slot['starts_at'])->format('d.m.Y. H:i') }} · {{ $slot['duration_minutes'] }} min<br>
                <strong>Do {{ number_format($price, 2, ',', '.') }} RSD za ceo teren.</strong> Ekipa se dogovara kako deli troškove.
            </p>
            <form method="post" action="{{ route('group-matches.store') }}" class="group-match-form">
                @csrf
                @foreach ($slot as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                <input type="hidden" name="quoted_price" value="{{ $price }}">
                @include('group-matches.fields')
                @if ($account === null)
                    @include('group-matches.questionnaire', ['questionSport' => $sport->slug])
                @endif
                <label class="group-match-check"><input type="checkbox" name="has_team" value="1" @checked(old('has_team'))> Imam saigrače i poslaću im poziv za svoju stranu</label>
                <p class="group-match-help">Potvrdom prihvataš prikazani termin i cenu. Ime, prezime i ocena potvrđenih igrača vide se na oglasu. Kad se skupi cela ekipa, sistem može automatski rezervisati teren.</p>
                <button class="arena-button-primary" type="submit">Pošalji mejl za potvrdu</button>
            </form>
        </div>
    @endif
@endsection
