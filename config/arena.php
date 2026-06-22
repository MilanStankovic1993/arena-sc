<?php

return [
    'contact' => [
        'email' => env('CONTACT_MAIL_TO', env('MAIL_FROM_ADDRESS')),
        'name' => env('CONTACT_MAIL_NAME', env('APP_NAME')),
        'phone' => env('CONTACT_PHONE', '+381 60 111 222'),
        'instagram' => env('CONTACT_INSTAGRAM', 'https://www.instagram.com/scarena.rs/'),
    ],

    'seed_admin' => [
        'name' => env('SEED_ADMIN_NAME', 'Arena Administrator'),
        'email' => env('SEED_ADMIN_EMAIL'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],

    'location' => [
        'name' => env('LOCATION_NAME', 'Sportski centar Arena'),
        'address' => env('LOCATION_ADDRESS', 'Adranska 114, Kraljevo'),
        'label' => env('LOCATION_LABEL', 'Kraljevo, Srbija'),
        'city' => env('LOCATION_CITY', 'Kraljevo'),
        'region' => env('LOCATION_REGION', 'Raski okrug'),
        'postal_code' => env('LOCATION_POSTAL_CODE', '36000'),
        'country' => env('LOCATION_COUNTRY', 'RS'),
        'latitude' => (float) env('LOCATION_LATITUDE', '43.7412758'),
        'longitude' => (float) env('LOCATION_LONGITUDE', '20.6683283'),
        'maps_url' => env('LOCATION_MAPS_URL', 'https://maps.app.goo.gl/acaW8mYdBzuqCuuMA'),
        'map_embed_url' => env(
            'LOCATION_MAP_EMBED_URL',
            'https://www.openstreetmap.org/export/embed.html?bbox=20.6583283%2C43.7362758%2C20.6783283%2C43.7462758&amp;layer=mapnik&amp;marker=43.7412758%2C20.6683283'
        ),
    ],

    'seo' => [
        'default_title' => env('SEO_DEFAULT_TITLE', 'Sportski centar Arena | Padel i Basket 3x3 | Kraljevo'),
        'default_description' => env(
            'SEO_DEFAULT_DESCRIPTION',
            'Sportski centar Arena u Kraljevu. Rezervacije za padel i basket 3x3, cenovnik termina, clanarine, oprema, turniri i sportski dogadjaji na jednom mestu.'
        ),
        'default_keywords' => env(
            'SEO_DEFAULT_KEYWORDS',
            'sportski centar, sportski centar kraljevo, padel, basket 3x3, basket, kraljevo padel, kraljevo basket, arena, arena kraljevo, sportski centar arena, rezervacija termina, padel kraljevo, basket 3x3 kraljevo'
        ),
        'site_name' => env('SEO_SITE_NAME', 'Sportski centar Arena'),
    ],

    'memberships' => [
        'expiry_reminder_days' => (int) env('MEMBERSHIP_EXPIRY_REMINDER_DAYS', 3),
    ],
];
