<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $seo = config('arena.seo');
            $location = config('arena.location');
            $contactEmail = config('arena.contact.email');
            $contactPhone = config('arena.contact.phone');
            $contactInstagram = config('arena.contact.instagram');
            $defaultImage = asset('media/home/hero-exterior.png');
            $seoTitle = $title ?? $seo['default_title'];
            $seoDescription = $metaDescription ?? $seo['default_description'];
            $seoKeywords = $metaKeywords ?? $seo['default_keywords'];
            $seoCanonical = $canonical ?? request()->url();
            $seoImage = $metaImage ?? $defaultImage;
            $seoType = $metaType ?? 'website';
            $seoRobots = $metaRobots ?? 'index,follow,max-image-preview:large';
            $schema = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'WebSite',
                        '@id' => url('/#website'),
                        'url' => url('/'),
                        'name' => $seo['site_name'],
                        'inLanguage' => 'sr-RS',
                    ],
                    [
                        '@type' => 'SportsActivityLocation',
                        '@id' => url('/#sports-center'),
                        'name' => $location['name'],
                        'url' => url('/'),
                        'image' => $defaultImage,
                        'description' => $seo['default_description'],
                        'telephone' => $contactPhone,
                        'email' => $contactEmail,
                        'address' => [
                            '@type' => 'PostalAddress',
                            'streetAddress' => $location['address'],
                            'addressLocality' => $location['city'],
                            'addressRegion' => $location['region'],
                            'postalCode' => $location['postal_code'],
                            'addressCountry' => $location['country'],
                        ],
                        'geo' => [
                            '@type' => 'GeoCoordinates',
                            'latitude' => $location['latitude'],
                            'longitude' => $location['longitude'],
                        ],
                        'sameAs' => array_values(array_filter([$contactInstagram])),
                        'areaServed' => [
                            '@type' => 'City',
                            'name' => $location['city'],
                        ],
                        'keywords' => $seoKeywords,
                    ],
                ],
            ];
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0F2A1F">
        <title>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="keywords" content="{{ $seoKeywords }}">
        <meta name="robots" content="{{ $seoRobots }}">
        <meta name="author" content="{{ $seo['site_name'] }}">
        <meta name="geo.region" content="RS">
        <meta name="geo.placename" content="{{ $location['city'] }}">
        <meta name="geo.position" content="{{ $location['latitude'] }};{{ $location['longitude'] }}">
        <meta name="ICBM" content="{{ $location['latitude'] }}, {{ $location['longitude'] }}">
        <link rel="canonical" href="{{ $seoCanonical }}">
        <link rel="sitemap" type="application/xml" title="Sitemap" href="{{ route('sitemap') }}">
        <meta property="og:locale" content="sr_RS">
        <meta property="og:type" content="{{ $seoType }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:url" content="{{ $seoCanonical }}">
        <meta property="og:site_name" content="{{ $seo['site_name'] }}">
        <meta property="og:image" content="{{ $seoImage }}">
        <meta property="og:image:alt" content="{{ $seoTitle }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        <meta name="twitter:image" content="{{ $seoImage }}">
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen">
        @php
            $contactEmail = config('arena.contact.email');
            $contactPhone = config('arena.contact.phone');
            $contactInstagram = config('arena.contact.instagram');
        @endphp
        <div class="site-shell">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[38rem] bg-[radial-gradient(circle_at_top_left,rgba(245,245,242,0.16),transparent_30%),radial-gradient(circle_at_top_right,rgba(15,42,31,0.2),transparent_28%),linear-gradient(180deg,rgba(9,23,17,0.12),transparent_74%)]"></div>
            <span class="floating-orb floating-orb--sand absolute -left-16 top-24 -z-10 h-52 w-52"></span>
            <span class="floating-orb floating-orb--forest absolute right-0 top-[28rem] -z-10 h-72 w-72"></span>

            <header class="premium-header">
                <div class="premium-nav-shell">
                    <div class="site-grid premium-nav-layout">
                        <a href="{{ route('home') }}" class="premium-brand">
                            <img src="{{ asset('brand/arena-sc-mark.svg') }}" alt="Sportski centar Arena logo" class="brand-logo brand-logo--header">
                        </a>

                        <nav class="premium-nav-links premium-nav-links--center">
                            <a href="{{ route('home') }}" class="premium-nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}">Pocetna</a>
                            <a href="{{ route('about') }}" class="premium-nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}">O nama</a>
                            <a href="{{ route('sports.index') }}" class="premium-nav-link {{ request()->routeIs('sports.*') || request()->routeIs('courts.*') ? 'is-active' : '' }}">Tereni</a>
                            <a href="{{ route('equipment.index') }}" class="premium-nav-link {{ request()->routeIs('equipment.*') ? 'is-active' : '' }}">Oprema</a>
                            <a href="{{ route('price-list.index') }}" class="premium-nav-link {{ request()->routeIs('price-list.*') ? 'is-active' : '' }}">Cenovnik</a>
                            <a href="{{ route('events.index') }}" class="premium-nav-link {{ request()->routeIs('events.*') ? 'is-active' : '' }}">Dogadjaji</a>
                        </nav>

                        <div class="premium-nav-actions">
                            <div class="hidden items-center gap-3 xl:flex">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="arena-button-primary">Moj nalog</a>
                                @else
                                    <a href="{{ route('login') }}" class="site-link">Prijava</a>
                                    <a href="{{ route('register') }}" class="arena-button-primary">Registracija</a>
                                @endauth
                            </div>

                            <details class="relative xl:hidden">
                                <summary class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full border border-[color:var(--arena-sand-glow)] bg-[rgba(245,245,242,0.08)] text-[color:var(--arena-sand)] marker:content-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                                    </svg>
                                </summary>
                                <div class="mobile-sheet absolute right-0 top-[calc(100%+0.9rem)] z-20 w-[min(18rem,86vw)]">
                                    <div class="grid gap-3">
                                        <a href="{{ route('home') }}" class="site-link">Pocetna</a>
                                        <a href="{{ route('about') }}" class="site-link">O nama</a>
                                        <a href="{{ route('sports.index') }}" class="site-link">Tereni</a>
                                        <a href="{{ route('equipment.index') }}" class="site-link">Oprema</a>
                                        <a href="{{ route('price-list.index') }}" class="site-link">Cenovnik</a>
                                        <a href="{{ route('events.index') }}" class="site-link">Dogadjaji</a>
                                    </div>

                                    <div class="mt-5 grid gap-3">
                                        @auth
                                            <a href="{{ route('dashboard') }}" class="arena-button-primary w-full">Moj nalog</a>
                                        @else
                                            <a href="{{ route('login') }}" class="arena-button-secondary w-full">Prijava</a>
                                            <a href="{{ route('register') }}" class="arena-button-primary w-full">Registracija</a>
                                        @endauth
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </header>

            <main>
                @if (session('status'))
                    <div class="site-grid pt-6">
                        <div class="site-success-banner">
                            {{ session('status') }}
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>

            <footer class="site-grid pb-12 pt-16 sm:pt-20">
                <div class="premium-footer-shell">
                    <div class="site-footer-grid">
                        <div>
                            <img src="{{ asset('brand/arena-sc-mark.svg') }}" alt="Sportski centar Arena logo" class="brand-logo brand-logo--footer mt-5">
                        </div>

                        <div class="footer-links-grid">
                            <a href="{{ route('booking.index') }}">Rezervisi termin</a>
                            <a href="{{ route('sports.index') }}">Tereni</a>
                            <a href="{{ route('equipment.index') }}">Oprema</a>
                            <a href="{{ route('price-list.index') }}">Cenovnik</a>
                            <a href="{{ route('events.index') }}">Dogadjaji</a>
                            <a href="{{ route('about') }}">O nama</a>
                            @auth
                                <a href="{{ route('dashboard') }}">Moj nalog</a>
                            @else
                                <a href="{{ route('register') }}">Registracija</a>
                            @endauth
                        </div>
                    </div>

                    <div class="footer-contact-grid">
                        <div class="footer-contact-card">
                            <span class="footer-kicker">Kontaktiraj nas</span>
                            <div class="footer-contact-list">
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contactPhone) }}">{{ $contactPhone }}</a>
                                <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                                <a href="{{ $contactInstagram }}" target="_blank" rel="noopener noreferrer">Instagram</a>
                            </div>
                        </div>

                        <div class="footer-contact-card footer-contact-card--action">
                            <span class="footer-kicker">Direktan kontakt</span>
                            <div class="footer-contact-actions">
                                <a href="{{ route('home') }}#kontaktiraj-nas" class="arena-button-primary">Kontaktiraj nas</a>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
