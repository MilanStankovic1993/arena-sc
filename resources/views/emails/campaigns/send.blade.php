<x-mail-layout :title="$campaign->subject">
    @php
        $ctaLabel = filled($campaign->cta_label) ? $campaign->cta_label : null;
        $ctaUrl = filled($campaign->cta_url) ? $campaign->cta_url : null;
    @endphp

    <div style="color:#1a1a1a;">
        @if ($campaign->preheader)
            <p style="margin:0 0 14px;font-size:11px;letter-spacing:0.26em;text-transform:uppercase;color:#7d8078;">
                {{ $campaign->preheader }}
            </p>
        @endif

        @if ($campaign->hero_image_url)
            <img
                src="{{ $campaign->hero_image_url }}"
                alt="{{ $campaign->name }}"
                style="display:block;width:100%;height:auto;max-height:320px;object-fit:cover;border-radius:16px;margin:0 0 22px;"
            >
        @endif

        <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.26em;text-transform:uppercase;color:#c8b89a;">
            Sportski centar Arena
        </p>

        <h1 style="margin:0 0 16px;font-size:34px;line-height:1.05;font-weight:800;color:#0f2a1f;">
            {{ $campaign->heading ?: $campaign->subject }}
        </h1>

        <p style="margin:0 0 18px;font-size:16px;line-height:1.7;color:#4f514d;">
            Zdravo{{ filled($recipient->name) ? ', ' . $recipient->name : '' }}!
        </p>

        @if ($campaign->intro)
            <p style="margin:0 0 18px;font-size:16px;line-height:1.8;color:#4f514d;white-space:pre-line;">
                {{ $campaign->intro }}
            </p>
        @endif

        <div style="margin:0 0 22px;font-size:16px;line-height:1.85;color:#2d2f2c;white-space:pre-line;">
            {{ $campaign->body }}
        </div>

        @if ($ctaLabel && $ctaUrl)
            <div style="margin:0 0 24px;">
                <a
                    href="{{ $ctaUrl }}"
                    style="display:inline-block;border-radius:14px;background:#c8b89a;color:#0f2a1f;text-decoration:none;font-size:13px;font-weight:800;letter-spacing:0.18em;text-transform:uppercase;padding:15px 26px;"
                >
                    {{ $ctaLabel }}
                </a>
            </div>
        @endif

        @if ($campaign->footer_note)
            <p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#6b6e68;white-space:pre-line;">
                {{ $campaign->footer_note }}
            </p>
        @endif

        <p style="margin:28px 0 0;font-size:14px;line-height:1.7;color:#7d8078;">
            Sportski centar Arena
        </p>
    </div>
</x-mail-layout>
