<x-mail-layout title="Serija rezervacija | Sportski centar Arena">
    <h1>{{ $cancelled ? 'Otkazani termini' : 'Rezervisani termini' }}</h1>
    <p>{{ $reservations->first()->customer_display_name }} · {{ $reservations->first()->court->name }}</p>
    <p>{{ $cancelled ? 'Sledeci termini su otkazani:' : 'Sledeci termini su potvrđeni:' }}</p>
    <table style="width:100%;border-collapse:collapse;">
        <thead><tr><th align="left">Datum</th><th align="left">Vreme</th><th align="right">Cena</th></tr></thead>
        <tbody>
        @foreach ($reservations as $reservation)
            <tr>
                <td style="padding:8px 0;">{{ $reservation->starts_at->format('d.m.Y') }}</td>
                <td>{{ $reservation->starts_at->format('H:i') }}–{{ $reservation->ends_at->format('H:i') }}</td>
                <td align="right">{{ number_format((float) $reservation->total_price, 0, ',', '.') }} RSD</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p>Ukupno: {{ $reservations->count() }} termina · {{ number_format($reservations->sum('total_price'), 0, ',', '.') }} RSD</p>
</x-mail-layout>
