@extends('layouts.site', ['title' => 'O nama | Arena SC'])

@section('content')
    <section class="site-grid py-10 sm:py-12">
        <div class="page-stack">
            <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr] xl:gap-8">
                <div class="page-hero-dark overflow-hidden">
                    <span class="dark-eyebrow-chip">O nama</span>
                    <h1 class="hero-title-dark mt-6 text-5xl sm:text-6xl">Mesto za rekreativce, timove i sportske dogadjaje.</h1>
                    <p class="hero-copy-dark mt-5">
                        Ovo je pocetni tekst stranice O nama. Kasnije ovde unosimo tvoju stvarnu pricu o centru, prostoru, timu, trenerima, ambijentu i razlozima zasto korisnici biraju bas Arena SC.
                    </p>
                </div>

                <div class="space-y-6">
                    <div class="premium-card p-8">
                        <span class="eyebrow-chip">Utisak prostora</span>
                        <h2 class="section-title mt-5">Profesionalno, pregledno i dovoljno ozbiljno da ljudi veruju sistemu.</h2>
                        <p class="hero-copy mt-5 text-sm">
                            Korisnik ovde treba da oseti da je prostor moderan i pouzdan, da su termini jasni, pravila transparentna i da iza svega postoji ozbiljna organizacija za rezervacije, ligu i dogadjaje.
                        </p>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="premium-card p-6">
                            <p class="text-xs font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest-glow)]">Misija</p>
                            <p class="mt-3 text-sm leading-7 text-[color:var(--arena-muted)]">Podrazumevani tekst koji kasnije zamenjujemo tvojim konkretnim sadrzajem i tonom komunikacije centra.</p>
                        </div>
                        <div class="premium-card p-6">
                            <p class="text-xs font-extrabold uppercase tracking-[0.3em] text-[color:var(--arena-forest)]">Vizija</p>
                            <p class="mt-3 text-sm leading-7 text-[color:var(--arena-muted)]">Ovde mozemo kasnije ubaciti plan razvoja centra, lige, turnira, clanstava i dodatnih programa za igrace.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
