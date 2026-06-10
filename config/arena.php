<?php

return [
    'contact' => [
        'email' => env('CONTACT_MAIL_TO', env('MAIL_FROM_ADDRESS')),
        'name' => env('CONTACT_MAIL_NAME', env('APP_NAME')),
        'phone' => env('CONTACT_PHONE', '+381 60 111 222'),
        'instagram' => env('CONTACT_INSTAGRAM', 'https://www.instagram.com/scarena.rs/'),
    ],

    'location' => [
        'name' => env('LOCATION_NAME', 'Sportski centar Arena'),
        'address' => env('LOCATION_ADDRESS', 'Adranska 114, Kraljevo'),
        'label' => env('LOCATION_LABEL', 'Kraljevo, Srbija'),
        'maps_url' => env('LOCATION_MAPS_URL', 'https://maps.app.goo.gl/acaW8mYdBzuqCuuMA'),
        'map_embed_url' => env(
            'LOCATION_MAP_EMBED_URL',
            'https://www.openstreetmap.org/export/embed.html?bbox=20.6583283%2C43.7362758%2C20.6783283%2C43.7462758&amp;layer=mapnik&amp;marker=43.7412758%2C20.6683283'
        ),
    ],

    'memberships' => [
        'expiry_reminder_days' => (int) env('MEMBERSHIP_EXPIRY_REMINDER_DAYS', 3),
    ],
];
