<x-mail-layout title="Potvrdite email adresu | Sportski Centar Arena">
    @php
        $website = parse_url(config('app.url'), PHP_URL_HOST) ?: 'scarena.rs';
    @endphp

    <div style="text-align:center;margin:0 0 24px;">
        <div style="color:#0f2a1f;font-size:24px;font-weight:900;letter-spacing:0.12em;text-transform:uppercase;line-height:1.15;">
            Sportski Centar Arena
        </div>
        <div style="width:72px;height:2px;background:#c8b89a;margin:16px auto 0;"></div>
    </div>

    <h1 style="margin:0 0 18px;color:#0f2a1f;font-size:30px;line-height:1.1;font-weight:900;">
        Dobrodošli!
    </h1>

    <p style="margin:0 0 16px;color:#475569;font-size:16px;line-height:1.8;">
        Uspešno ste kreirali nalog na Sportskom Centru Arena.
    </p>

    <p style="margin:0 0 28px;color:#475569;font-size:16px;line-height:1.8;">
        Kako biste mogli da rezervišete termine, potrebno je da potvrdite svoju email adresu.
    </p>

    <div style="text-align:center;margin:0 0 28px;">
        <a href="{{ $url }}" style="display:inline-block;background:#c8b89a;color:#0f2a1f;text-decoration:none;text-transform:uppercase;letter-spacing:0.14em;font-size:13px;font-weight:900;padding:16px 28px;border-radius:999px;">
            Potvrdi email
        </a>
    </div>

    <p style="margin:0 0 28px;color:#64748b;font-size:14px;line-height:1.8;">
        Ukoliko niste kreirali nalog, slobodno ignorišite ovu poruku.
    </p>

    <div style="border-top:1px solid #e8e5db;padding-top:20px;color:#0f2a1f;font-size:14px;line-height:1.9;">
        <div><strong>Lokacija:</strong> {{ config('arena.location.address') }}</div>
        <div><strong>Email:</strong> {{ config('arena.contact.email') }}</div>
        <div><strong>Web:</strong> {{ $website }}</div>
        <div><strong>Telefon:</strong> {{ config('arena.contact.phone') }}</div>
    </div>

    <div style="margin-top:22px;text-align:center;color:#94a3b8;font-size:12px;">
        © {{ now()->year }} Sportski Centar Arena
    </div>
</x-mail-layout>
