<x-mail-layout :title="$match->sport->name.' | Pronađi ekipu'">
    <h1 style="margin:0 0 16px;color:#0f2a1f;font-size:26px;">
        @switch($kind)
            @case('verify') Potvrdi mesto u ekipi @break
            @case('booked') Termin je rezervisan, čekamo vas @break
            @case('cancelled') Grupni termin je otkazan @break
            @default Grupni termin nije potvrđen
        @endswitch
    </h1>
    <p>Zdravo, {{ $player->name }}!</p>
    <p>{{ $match->sport->name }} · {{ $match->starts_at->format('d.m.Y. H:i') }}–{{ $match->ends_at->format('H:i') }}</p>
    @if ($kind === 'verify')
        @if ($player->needsQuestionnaire())
            <p>Pozvan si da igraš u ekipi {{ $player->side }}. Otvori prijavu, popuni upitnik i potvrdi svoje mesto do {{ $match->closes_at->format('H:i') }}.</p>
        @else
            <p>Otvori prijavu i potvrdi svoje mesto do {{ $match->closes_at->format('H:i') }}.</p>
        @endif
        <p><strong>Do {{ number_format((float) $match->quoted_price, 2, ',', '.') }} RSD za ceo teren.</strong> Ekipa se dogovara kako deli troškove.</p>
        <p><a href="{{ $verificationUrl }}" style="display:inline-block;padding:12px 16px;background:#0f2a1f;color:#fff;border-radius:8px;">Otvori svoju prijavu</a></p>
    @elseif ($kind === 'booked')
        <p><strong>Teren</strong> {{ $match->reservation?->court?->name }}</p>
        <p><strong>Ukupna cena terena</strong> {{ number_format((float) $match->reservation?->total_price, 2, ',', '.') }} RSD. Ekipa se dogovara kako deli troškove.</p>
    @elseif ($kind === 'cancelled')
        <p>Arena je otkazala rezervaciju ovog grupnog termina.</p>
    @else
        <p>{{ $match->closing_note }}</p>
        <p><a href="{{ route('group-matches.index') }}">Pogledaj druge grupne termine</a></p>
    @endif
</x-mail-layout>
