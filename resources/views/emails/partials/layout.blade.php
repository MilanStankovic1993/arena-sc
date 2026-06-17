<!DOCTYPE html>
<html lang="sr">
    <head>
        <meta charset="utf-8">
        <title>{{ $title ?? 'Sportski centar Arena' }}</title>
    </head>
    <body style="margin:0;padding:0;background:#f5f5f2;color:#1a1a1a;font-family:Arial,Helvetica,sans-serif;">
        <div style="max-width:680px;margin:0 auto;padding:32px 18px;">
            <div style="background:#0f2a1f;border-radius:20px;padding:28px 28px 24px;box-shadow:0 18px 50px rgba(15,42,31,0.18);">
                <div style="margin-bottom:24px;">
                    <img src="{{ asset('brand/arena-sc-mark.svg') }}" alt="Sportski centar Arena" style="display:block;max-width:220px;height:auto;">
                </div>

                <div style="background:#ffffff;border-radius:18px;padding:28px;">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
