@extends('layouts.site', ['title' => 'O nama | Arena SC'])

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <div class="site-card overflow-hidden bg-[linear-gradient(135deg,rgba(13,59,102,0.98),rgba(215,38,61,0.9))] p-8 text-white">
                <p class="text-sm font-bold uppercase tracking-[0.3em] text-blue-100">O nama</p>
                <h1 class="mt-4 text-5xl font-black leading-none">Mesto za rekreativce, timove i sportske događaje.</h1>
                <p class="mt-6 text-base leading-8 text-white/75">
                    Ovo je početni tekst stranice O nama. Kasnije možemo uneti tvoju stvarnu priču o centru, prostoru, timu, trenerima, ambijentu i razlozima zašto korisnici biraju baš Arena SC.
                </p>
            </div>

            <div class="grid gap-6">
                <div class="site-card p-8">
                    <h2 class="text-2xl font-black text-[var(--arena-blue)]">Sta ovde korisnik treba da oseti</h2>
                    <p class="mt-4 text-sm leading-8 text-slate-600">
                        Da je prostor profesionalan, ali prijatan. Da su termini pregledni. Da su pravila jasna. Da postoji ozbiljan sistem za turnire, ligu i statistiku, a da je korisniku rezervacija ipak laka i brza.
                    </p>
                </div>
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="site-card p-6">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-[var(--arena-red)]">Misija</p>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Podrazumevani tekst koji kasnije zamenjujemo tvojim konkretnim sadržajem.</p>
                    </div>
                    <div class="site-card p-6">
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-[var(--arena-blue-soft)]">Vizija</p>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Ovde možemo kasnije ubaciti plan razvoja centra, lige, događaja i programa za članove.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
