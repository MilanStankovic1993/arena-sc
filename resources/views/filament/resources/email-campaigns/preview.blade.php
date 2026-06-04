<div class="space-y-6 p-2">
    <div class="rounded-[1.5rem] border border-[rgba(15,42,31,0.1)] bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Mail kampanja</p>
                <h3 class="mt-2 text-2xl font-black text-[color:var(--arena-forest)]">{{ $campaign->name }}</h3>
            </div>
            @if ($campaign->is_active)
                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Aktivna</span>
            @endif
        </div>

        @if ($campaign->hero_image_url)
            <img src="{{ $campaign->hero_image_url }}" alt="{{ $campaign->name }}" class="mb-5 h-56 w-full rounded-[1.25rem] object-cover">
        @endif

        <div class="space-y-4">
            <div>
                <p class="text-xs uppercase tracking-[0.26em] text-slate-400">Naslov mejla</p>
                <p class="mt-1 text-lg font-semibold text-[color:var(--arena-forest)]">{{ $campaign->subject }}</p>
            </div>

            @if ($campaign->preheader)
                <div>
                    <p class="text-xs uppercase tracking-[0.26em] text-slate-400">Preheader</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $campaign->preheader }}</p>
                </div>
            @endif

            @if ($campaign->heading)
                <div>
                    <p class="text-xs uppercase tracking-[0.26em] text-slate-400">Veliki naslov</p>
                    <p class="mt-1 text-3xl font-black leading-none text-[color:var(--arena-forest)]">{{ $campaign->heading }}</p>
                </div>
            @endif

            @if ($campaign->intro)
                <div>
                    <p class="text-xs uppercase tracking-[0.26em] text-slate-400">Uvod</p>
                    <p class="mt-1 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $campaign->intro }}</p>
                </div>
            @endif

            <div>
                <p class="text-xs uppercase tracking-[0.26em] text-slate-400">Glavni tekst</p>
                <p class="mt-1 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $campaign->body }}</p>
            </div>

            @if ($campaign->cta_label || $campaign->cta_url)
                <div>
                    <p class="text-xs uppercase tracking-[0.26em] text-slate-400">CTA</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $campaign->cta_label ?: 'Bez teksta dugmeta' }}</p>
                    @if ($campaign->cta_url)
                        <p class="mt-1 text-sm text-slate-500">{{ $campaign->cta_url }}</p>
                    @endif
                </div>
            @endif

            @if ($campaign->footer_note)
                <div>
                    <p class="text-xs uppercase tracking-[0.26em] text-slate-400">Zavrsna napomena</p>
                    <p class="mt-1 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $campaign->footer_note }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
