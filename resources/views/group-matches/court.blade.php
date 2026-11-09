<div class="group-match-court {{ $match->sport->slug === 'padel' ? 'group-match-court--padel' : 'group-match-court--basket' }}" aria-label="Raspored mesta na terenu">
    @if ($match->sport->slug === 'padel')
        <svg class="group-match-court-lines" aria-hidden="true" viewBox="0 0 640 230" preserveAspectRatio="none" fill="none">
            <path d="M16 16h608v198H16zM320 16v198M150 16v198M490 16v198M150 115h340" vector-effect="non-scaling-stroke"/>
            <path class="group-match-court-net" d="M320 10v210" vector-effect="non-scaling-stroke"/>
        </svg>
    @else
        <svg class="group-match-court-lines" aria-hidden="true" viewBox="0 0 640 230" preserveAspectRatio="none" fill="none">
            <path d="M16 16h608v198H16zM250 16v108h140V16M250 124a70 37 0 0 0 140 0M172 16v26a148 135 0 0 0 296 0V16M296 27h48" vector-effect="non-scaling-stroke"/>
            <circle cx="320" cy="43" r="11" vector-effect="non-scaling-stroke"/>
        </svg>
    @endif
    @foreach ($occupied as $side => $count)
        <div class="group-match-court-team">
            <strong>Ekipa {{ $side }}</strong>
            <span>{{ $count }}/{{ $match->team_size }} popunjeno</span>
            <div class="group-match-court-seats">
                @for ($position = 1; $position <= $match->team_size; $position++)
                    @if ($position <= $count)
                        <span class="group-match-court-seat group-match-court-seat--taken" role="img" aria-label="Zauzeto mesto {{ $position }} u ekipi {{ $side }}">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="7" r="3"/><path d="M5 20v-1a7 7 0 0 1 14 0v1"/></svg>
                        </span>
                    @elseif ($accepting && in_array($side, $joinableSides, true))
                        <a class="group-match-court-seat group-match-court-seat--open"
                           href="{{ route('group-matches.show', $matchParameters + ['strana' => $side, 'mesto' => $position]) }}#prijava"
                           data-group-seat data-side="{{ $side }}" data-position="{{ $position }}"
                           aria-label="Izaberi slobodno mesto {{ $position }} u ekipi {{ $side }}">+</a>
                    @else
                        <span class="group-match-court-seat group-match-court-seat--closed" role="img" aria-label="Mesto {{ $position }} u ekipi {{ $side }} nije otvoreno za prijavu">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="6" y="11" width="12" height="9" rx="2"/><path d="M9 11V7a3 3 0 0 1 6 0v4"/></svg>
                        </span>
                    @endif
                @endfor
            </div>
            @if ($side === 'A' && $match->team_invite)
                <span class="group-match-court-note">Prijava po pozivu</span>
            @endif
        </div>
    @endforeach
</div>
