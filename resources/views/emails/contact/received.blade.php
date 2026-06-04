<x-mail-layout title="Nova kontakt poruka | Arena SC">
    <h1 style="margin:0 0 16px;color:#0f2a1f;font-size:28px;line-height:1.1;">Nova kontakt poruka</h1>

    <p style="margin:0 0 18px;color:#5b6874;font-size:15px;line-height:1.7;">
        Stigla je nova poruka preko kontakt forme na sajtu.
    </p>

    <div style="display:grid;gap:12px;margin:0 0 22px;">
        <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;">
            <strong>Ime i prezime:</strong> {{ $payload['name'] }}
        </div>
        <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;">
            <strong>Email:</strong> {{ $payload['email'] }}
        </div>
        @if (! empty($payload['phone']))
            <div style="padding:14px 16px;border:1px solid #e8e5db;border-radius:14px;background:#f5f5f2;">
                <strong>Telefon:</strong> {{ $payload['phone'] }}
            </div>
        @endif
    </div>

    <div style="padding:18px;border:1px solid #e8e5db;border-radius:16px;background:#fff;">
        <strong style="display:block;margin-bottom:10px;color:#0f2a1f;">Poruka</strong>
        <p style="margin:0;color:#475569;font-size:15px;line-height:1.8;white-space:pre-line;">{{ $payload['message'] }}</p>
    </div>
</x-mail-layout>
