<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Event;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_include_seo_metadata_and_sitemap(): void
    {
        $sport = Sport::query()->create([
            'name' => 'Padel',
            'short_description' => 'Padel teren',
            'is_active' => true,
            'supports_online_booking' => true,
            'sort_order' => 1,
        ]);

        $court = Court::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel teren 1',
            'is_active' => true,
        ]);

        $event = Event::query()->create([
            'title' => 'Padel turnir Kraljevo',
            'type' => 'tournament',
            'status' => 'registration',
            'is_featured' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('meta name="description"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('application/ld+json', false);

        $this->get(route('courts.show', $court))
            ->assertOk()
            ->assertSee('Padel teren 1', false)
            ->assertSee('Rezervisi termin za ovaj teren');

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Padel turnir Kraljevo', false);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'), false)
            ->assertSee(route('courts.show', $court), false)
            ->assertSee(route('events.show', $event), false);
    }
}
