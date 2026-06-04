<x-mail-layout :title="$mode === 'cancelled' ? 'Otkazana rezervacija | Arena SC' : 'Nova rezervacija | Arena SC'">
    <h1 style="margin:0 0 16px;color:#0f2a1f;font-size:28px;line-height:1.1;">
        {{ $mode === 'cancelled' ? 'Otkazana rezervacija' : 'Nova rezervacija' }}
    </h1>

    <p style="margin:0 0 18px;color:#475569;font-size:15px;line-height:1.8;">
        Korisnik {{ $reservation->user->name }} ({{ $reservation->user->email }}) ima novu promenu u sistemu rezervacija.
    </p>

    <div style="display:grid;gap:12px;">
        <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;"><strong>Status:</strong> {{ $reservation->status->label() }}</div>
        <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;"><strong>Sport:</strong> {{ $reservation->sport->name }}</div>
        <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;"><strong>Teren:</strong> {{ $reservation->court->name }}</div>
        <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;"><strong>Termin:</strong> {{ $reservation->starts_at->format('d.m.Y H:i') }} - {{ $reservation->ends_at->format('H:i') }}</div>
        <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;"><strong>Ukupna cena:</strong> {{ number_format((float) $reservation->total_price, 0, ',', '.') }} RSD</div>
    </div>
</x-mail-layout>
