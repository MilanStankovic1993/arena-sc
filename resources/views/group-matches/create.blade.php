@extends('group-matches.layout', [
    'title' => 'Kreiraj novi grupni termin',
    'intro' => 'Proveri izabrani termin i pošalji prijavu. Oglas će se pojaviti na listi kada potvrdiš mejl.',
])

@section('intro-actions')
    <a class="arena-button-secondary" href="{{ route('group-matches.create', ['sport' => $sport->slug, 'date' => substr($slot['starts_at'], 0, 10)]) }}">Promeni termin</a>
@endsection

@section('matches-content')
        <div class="booking-panel group-match-form-panel">
            <h2>Tvoja prijava</h2>
            <p class="booking-inline-summary">{{ $sport->name }} · {{ \Carbon\Carbon::parse($slot['starts_at'])->format('d.m.Y. H:i') }} · {{ $slot['duration_minutes'] }} min<br>
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
@endsection
