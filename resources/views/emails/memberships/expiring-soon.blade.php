<x-mail-layout title="Članarina uskoro istice | Sportski Centar Arena">
    <h1 style="margin:0 0 16px;font-size:28px;line-height:1.15;color:#0f2a1f;">Članarina uskoro istice.</h1>

    <p style="margin:0 0 18px;line-height:1.7;color:#36443e;">
        Pozdrav {{ $membership->user->name }}, vasa članarina istice za {{ $daysBeforeExpiry }} dana.
    </p>

    <div style="border:1px solid rgba(15,42,31,0.12);border-radius:16px;padding:18px;background:#f5f5f2;margin:20px 0;">
        <p style="margin:0 0 8px;color:#0f2a1f;"><strong>Članarina:</strong> {{ $membership->membershipPlan->name }}</p>
        <p style="margin:0 0 8px;color:#0f2a1f;"><strong>Sport:</strong> {{ $membership->membershipPlan->sport?->name ?? 'Svi sportovi' }}</p>
        <p style="margin:0 0 8px;color:#0f2a1f;"><strong>Vazi do:</strong> {{ $membership->ends_at->format('d.m.Y') }}</p>
        <p style="margin:0;color:#0f2a1f;"><strong>Limit:</strong> {{ $membership->membershipPlan->reservation_limit }} termina ukupno tokom trajanja clanarine</p>
    </div>

    <p style="margin:0 0 20px;line-height:1.7;color:#36443e;">
        Ako zelite da produzite članarinu, kontaktirajte nas ili svratite u Sportski Centar Arena.
    </p>

    <p style="margin:0;">
        <a href="{{ config('arena.location.maps_url') }}" style="display:inline-block;border-radius:14px;background:#c8b89a;color:#0f2a1f;text-decoration:none;padding:13px 20px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">
            Kontakt i lokacija
        </a>
    </p>
</x-mail-layout>
