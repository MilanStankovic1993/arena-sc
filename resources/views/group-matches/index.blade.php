@extends('group-matches.layout', [
    'title' => 'Pronađi ekipu',
    'intro' => 'Pridruži se otvorenom grupnom terminu ili okupi svoju ekipu. Teren se rezerviše kad se popune obe strane i ima slobodnog mesta.',
])

@section('intro-actions')
    @if (config('arena.booking.is_open') && $sports->isNotEmpty())
        <a class="arena-button-primary" href="{{ route('group-matches.create', ['sport' => $sport?->slug]) }}">Kreiraj novi grupni termin</a>
    @endif
    <a class="arena-button-secondary" href="{{ route('booking.index') }}">Redovna rezervacija</a>
    @auth
        <a class="arena-button-secondary" href="{{ route('player-profiles.index', ['sport' => $sport?->slug]) }}">Igrači i rezultati</a>
    @endauth
@endsection

@section('matches-content')
    <div class="group-match-list-heading">
        <h2>Otvoreni grupni termini</h2>
        <nav aria-label="Filtriraj po sportu" class="group-match-filters">
            <a @if (!$sport) aria-current="page" @endif href="{{ route('group-matches.index') }}">Svi sportovi</a>
            @foreach ($sports as $option)
                <a @if ($sport?->is($option)) aria-current="page" @endif href="{{ route('group-matches.index', ['sport' => $option->slug]) }}">{{ $option->name }}</a>
            @endforeach
        </nav>
    </div>
    <div class="group-match-grid">
        @forelse ($matches as $match)
            <article class="booking-panel group-match-card">
                <div class="group-match-card-top">
                    <span class="eyebrow-chip">{{ $match->sport->name }}</span>
                    <span class="group-match-badge">{{ $match->status_label }}</span>
                </div>
                <h3>{{ $match->starts_at->format('d.m.Y.') }} · {{ $match->starts_at->format('H:i') }}–{{ $match->ends_at->format('H:i') }}</h3>
                <p>
                    <strong>{{ $match->confirmed_players_count }}/{{ $match->capacity() }} igrača</strong>
                    @if ($match->average_rating !== null)
                        · Prosek ocena <strong>{{ (int) round($match->average_rating) }}/10</strong>
                    @endif
                </p>
                <p><strong>Do {{ number_format((float) $match->quoted_price, 2, ',', '.') }} RSD za ceo teren.</strong> Ekipa se dogovara kako deli troškove.</p>
                <a class="arena-button-secondary" href="{{ route('group-matches.show', $match) }}">{{ $match->status === \App\Models\GroupMatch::Open ? 'Pogledaj i prijavi se' : 'Pogledaj termin' }}</a>
            </article>
        @empty
            <article class="booking-panel group-match-empty">
                <h3>Trenutno nema grupnih termina za ovaj izbor.</h3>
                <p>Kada organizator potvrdi mejl, njegov oglas će se pojaviti ovde.</p>
            </article>
        @endforelse
    </div>
    {{ $matches->links() }}
@endsection
