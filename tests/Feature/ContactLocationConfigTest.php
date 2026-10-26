<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactLocationConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_and_structured_data_use_the_updated_phone(): void
    {
        config(['arena.contact.phone' => '+381628025292']);

        foreach (['/', '/o-nama', '/sportovi', '/cenovnik', '/oprema', '/dogadjaji', '/rezervisi-termin'] as $path) {
            $this->get($path)->assertOk()
                ->assertSee('tel:+381628025292', false)
                ->assertSee('+381628025292')
                ->assertDontSee('+381 60 111 222');
        }
    }

    public function test_homepage_uses_the_google_profile_link_and_its_embedded_place(): void
    {
        config([
            'arena.location.maps_url' => 'https://share.google/yOGRjDke9knKkLApx',
            'arena.location.map_embed_url' => 'https://maps.google.com/maps?cid=17559082527099626823&output=embed',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('href="https://share.google/yOGRjDke9knKkLApx"', false)
            ->assertSee('src="https://maps.google.com/maps?cid=17559082527099626823&amp;output=embed"', false)
            ->assertDontSee('acaW8mYdBzuqCuuMA')
            ->assertDontSee('openstreetmap.org/export/embed.html');
    }
}
