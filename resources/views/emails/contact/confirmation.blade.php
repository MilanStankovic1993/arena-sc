<x-mail-layout title="Primili smo vašu poruku | Arena SC">
    <h1 style="margin:0 0 16px;color:#0f2a1f;font-size:28px;line-height:1.1;">Primili smo vašu poruku</h1>

    <p style="margin:0 0 14px;color:#475569;font-size:15px;line-height:1.8;">
        Hvala vam, {{ $payload['name'] }}. Vaša poruka je uspešno poslata i naš tim će vam se javiti u najkraćem roku.
    </p>

    <div style="padding:18px;border:1px solid #e8e5db;border-radius:16px;background:#f5f5f2;">
        <strong style="display:block;margin-bottom:10px;color:#0f2a1f;">Sažetak poruke</strong>
        <p style="margin:0;color:#5b6874;font-size:15px;line-height:1.8;white-space:pre-line;">{{ $payload['message'] }}</p>
    </div>
</x-mail-layout>
