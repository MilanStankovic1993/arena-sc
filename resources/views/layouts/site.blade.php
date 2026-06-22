<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $seo = config('arena.seo');
            $location = config('arena.location');
            $contactEmail = config('arena.contact.email');
            $contactPhone = config('arena.contact.phone');
            $contactInstagram = config('arena.contact.instagram');
            $defaultImage = asset('media/home/hero-exterior.webp');
            $seoTitle = $title ?? $seo['default_title'];
            $seoDescription = $metaDescription ?? $seo['default_description'];
            $seoKeywords = $metaKeywords ?? $seo['default_keywords'];
            $seoCanonical = $canonical
                ?? rtrim((string) config('app.url'), '/').'/'.ltrim(request()->getPathInfo(), '/');
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
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Great+Vibes&display=swap">
        @stack('head')
        <script type="application/ld+json">{{ Illuminate\Support\Js::from($schema) }}</script>
        @vite('resources/css/app.css')
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

            @include('layouts.partials.site-header')

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

            @include('layouts.partials.quick-actions')

            <footer class="site-grid pb-12 pt-16 sm:pt-20">
                <div class="premium-footer-shell">
                    <div class="site-footer-grid">
                        <div>
                            <img src="{{ asset('brand/arena-sc-mark.webp') }}" alt="Sportski centar Arena logo" width="640" height="360" loading="lazy" decoding="async" class="brand-logo brand-logo--footer mt-5">
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
