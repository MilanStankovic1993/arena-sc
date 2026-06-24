<x-mail-layout title="Rezervacija je otkazana | Sportski centar Arena">
    <h1 style="margin:0 0 16px;color:#0f2a1f;font-size:28px;line-height:1.1;">Rezervacija je otkazana</h1>

    <p style="margin:0 0 18px;color:#475569;font-size:15px;line-height:1.8;">
        Termin za {{ $reservation->court->name }} je otkazan.
    </p>

    <div style="display:grid;gap:12px;">
        <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;"><strong>Sport:</strong> {{ $reservation->sport->name }}</div>
        <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;"><strong>Teren:</strong> {{ $reservation->court->name }}</div>
        <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;"><strong>Termin:</strong> {{ $reservation->starts_at->format('H:i') }} - {{ $reservation->ends_at->format('H:i') }} ({{ $reservation->starts_at->format('d.m.Y') }})</div>
        @if ($reservation->cancellation_reason)
            <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;"><strong>Razlog:</strong> {{ $reservation->cancellation_reason }}</div>
        @endif
    </div>
</x-mail-layout>
