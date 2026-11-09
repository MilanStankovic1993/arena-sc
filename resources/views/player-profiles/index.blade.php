@extends('group-matches.layout', [
    'title' => 'Igrači i rezultati',
    'intro' => 'Uporedi učinak po sportu, vodi svoje rezultate i pozovi igrače na grupni termin.',
])

@section('intro-actions')
    <a class="arena-button-primary" href="{{ route('group-matches.index', ['sport' => $sport?->slug]) }}">Grupni termini</a>
    <a class="arena-button-secondary" href="{{ route('dashboard') }}">Moj nalog</a>
@endsection

@section('matches-content')
    @if ($sport)
        <nav aria-label="Izaberi sport" class="group-match-filters">
            @foreach ($sports as $option)
                <a @if ($sport->is($option)) aria-current="page" @endif href="{{ route('player-profiles.index', ['sport' => $option->slug]) }}">{{ $option->name }}</a>
            @endforeach
        </nav>

        <section class="booking-panel player-profile-panel">
            <h2>Moj profil · {{ $sport->name }}</h2>
            @if ($profile)
                <p>Ocena za prijavu na termin <strong>{{ (int) round($profile->ratings_avg_score ?? $profile->starting_rating) }}/10</strong>.</p>
            @endif
            <p class="group-match-help">Aktiviranjem profila prikazuješ svoje ime, ocenu i lične rezultate na tabeli za ovaj sport. Početna ocena se koristi dok ne dobiješ ocene drugih igrača.</p>
            <form method="post" action="{{ route('player-profiles.save', ['sport' => $sport->slug]) }}" class="group-match-form">
                @csrf
                <label>Početna ocena od 1 do 10
                    <input type="number" name="starting_rating" min="1" max="10" required value="{{ old('starting_rating', $profile?->starting_rating ?? \App\Models\PlayerProfile::DefaultRating) }}">
                </label>
                <button type="submit" class="arena-button-primary">{{ $profile ? 'Sačuvaj profil' : 'Aktiviraj profil igrača' }}</button>
            </form>
        </section>

        <section class="booking-panel player-profile-panel">
            <h2>Tabela igrača</h2>
            <p class="group-match-help">Poredak je po broju pobeda, zatim po prosečnoj oceni drugih igrača. Rezultate igrači upisuju za sebe.</p>
            <div class="player-table-scroll" role="region" aria-label="Tabela igrača" tabindex="0">
                <table class="player-table">
                    <thead><tr>
                        <th scope="col">Igrač</th><th scope="col">Prosek ocena</th><th scope="col">Uneti rezultati</th>
                        <th scope="col">Pobede</th><th scope="col">Porazi</th><th scope="col">Nerešeno</th><th scope="col">Akcije</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($profiles as $entry)
                            <tr>
                                <th scope="row">{{ $entry->user->name }}</th>
                                <td>{{ $entry->ratings_count ? number_format($entry->ratings_avg_score, 1, ',', '.').'/10 ('.$entry->ratings_count.')' : 'Bez ocena' }}</td>
                                <td>{{ $entry->results_count }}</td><td>{{ $entry->wins }}</td>
                                <td>{{ $entry->results_count - $entry->wins - $entry->draws }}</td><td>{{ $entry->draws }}</td>
                                <td>
                                    @if ($entry->user_id === auth()->id())
                                        Ti
                                    @elseif ($profile || $upcoming->isNotEmpty())
                                        <details>
                                            <summary>Oceni ili pozovi</summary>
                                            @if ($profile)
                                                <form method="post" action="{{ route('player-profiles.rate', $entry) }}" class="group-match-form">
                                                    @csrf
                                                    <label>Tvoja ocena od 1 do 10
                                                        <input type="number" name="score" min="1" max="10" required value="{{ $myRatings[$entry->id] ?? '' }}">
                                                    </label>
                                                    <button type="submit" class="arena-button-secondary-light">Sačuvaj ocenu</button>
                                                </form>
                                            @endif
                                            @if ($upcoming->isNotEmpty())
                                                <form method="post" action="{{ route('player-profiles.invite', $entry) }}" class="group-match-form">
                                                    @csrf
                                                    <label>Izaberi svoj grupni termin
                                                        <select name="match" required>
                                                            @foreach ($upcoming as $match)
                                                                <option value="{{ $match->id }}">{{ $match->starts_at->format('d.m.Y. H:i') }} · {{ $match->duration_minutes }} min</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <div class="group-match-actions">
                                                        <button type="submit" name="challenge" value="0" class="arena-button-secondary-light">Pozovi</button>
                                                        <button type="submit" name="challenge" value="1" class="arena-button-secondary-light">Izazovi</button>
                                                    </div>
                                                </form>
                                            @endif
                                        </details>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Još nema aktiviranih profila za ovaj sport.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $profiles->links() }}
        </section>

        @if ($profile)
            <section class="booking-panel player-profile-panel">
                <h2>Moji rezultati</h2>
                <p class="group-match-help">Unesi rezultat za odigrani grupni termin. Možeš ga naknadno ispraviti.</p>
                @forelse ($played as $match)
                    @php($result = $myResults->get($match->id))
                    <form method="post" action="{{ route('player-profiles.result', $match) }}" class="group-match-form player-profile-item">
                        @csrf
                        <strong>{{ $match->reservation->starts_at->format('d.m.Y. H:i') }}</strong>
                        <div class="group-match-fields">
                            <label>{{ $sport->slug === 'padel' ? 'Setovi tvoje ekipe' : 'Poeni tvoje ekipe' }}
                                <input type="number" name="own_score" min="0" max="999" value="{{ $result?->own_score }}" required>
                            </label>
                            <label>{{ $sport->slug === 'padel' ? 'Setovi protivnika' : 'Poeni protivnika' }}
                                <input type="number" name="opponent_score" min="0" max="999" value="{{ $result?->opponent_score }}" required>
                            </label>
                        </div>
                        <button type="submit" class="arena-button-secondary-light">Sačuvaj rezultat</button>
                    </form>
                @empty
                    <p>Još nema odigranih grupnih termina prijavljenih sa tvog naloga.</p>
                @endforelse
                {{ $played->links() }}
            </section>
        @endif

        <section class="booking-panel player-profile-panel">
            <h2>Pozivi i izazovi</h2>
            <p class="group-match-help">Poziv se šalje na otvoreni grupni termin na kojem imaš potvrđeno mesto. Izazov je poziv na protivničku stranu. Igrač potvrđuje mesto kroz prijavu na termin.</p>
            @forelse ($incoming as $invitation)
                <div class="player-profile-item">
                    <p><strong>{{ $invitation->sender->name }}</strong> · {{ $invitation->challenge ? 'Izazov' : 'Poziv za igru' }} · {{ $invitation->groupMatch->starts_at->format('d.m.Y. H:i') }}</p>
                    <form method="post" action="{{ route('player-profiles.respond', $invitation) }}" class="group-match-actions">
                        @csrf
                        <button type="submit" name="accept" value="1" class="arena-button-primary">{{ $invitation->accepted ? 'Otvori termin' : 'Prihvati i otvori termin' }}</button>
                        @unless ($invitation->accepted)
                            <button type="submit" name="accept" value="0" class="arena-button-secondary-light">Odbij</button>
                        @endunless
                    </form>
                </div>
            @empty
                <p>Nema aktivnih primljenih poziva.</p>
            @endforelse
            @if ($outgoing->isNotEmpty())
                <h3>Poslati pozivi</h3>
                <ul>
                    @foreach ($outgoing as $invitation)
                        <li>{{ $invitation->recipient->name }} · {{ $invitation->challenge ? 'Izazov' : 'Poziv' }} · {{ $invitation->groupMatch->starts_at->format('d.m.Y. H:i') }} · {{ match ($invitation->accepted) { true => 'Prihvaćen', false => 'Odbijen', default => 'Čeka odgovor' } }}</li>
                    @endforeach
                </ul>
            @endif
        </section>
    @else
        <section class="booking-panel player-profile-panel"><p>Trenutno nema dostupnih sportova.</p></section>
    @endif
@endsection
