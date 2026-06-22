<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Event;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_events_are_not_exposed_on_public_pages_or_sitemap(): void
    {
        $draft = Event::query()->create([
            'title' => 'Tajni draft turnir',
            'type' => 'tournament',
            'status' => 'draft',
            'is_featured' => true,
        ]);

        $this->get(route('events.index'))->assertOk()->assertDontSee($draft->title);
        $this->get(route('events.show', $draft))->assertNotFound();
        $this->get(route('sitemap'))->assertOk()->assertDontSee(route('events.show', $draft), false);
    }

    public function test_inactive_courts_and_courts_of_inactive_sports_are_not_public(): void
    {
        $activeSport = $this->createSport('Aktivni sport', true);
        $inactiveCourt = $this->createCourt($activeSport, 'Sakriven teren', false);
        $inactiveSport = $this->createSport('Neaktivni sport', false);
        $orphanedCourt = $this->createCourt($inactiveSport, 'Teren neaktivnog sporta', true);

        $this->get(route('sports.index'))
            ->assertOk()
            ->assertDontSee($inactiveCourt->name)
            ->assertDontSee($inactiveSport->name);

        $this->get(route('courts.show', $inactiveCourt))->assertNotFound();
        $this->get(route('courts.show', $orphanedCourt))->assertNotFound();

        $sitemap = $this->get(route('sitemap'))->assertOk();
        $sitemap->assertDontSee(route('courts.show', $inactiveCourt), false);
        $sitemap->assertDontSee(route('courts.show', $orphanedCourt), false);
    }

    private function createSport(string $name, bool $active): Sport
    {
        return Sport::query()->create([
            'name' => $name,
            'short_description' => 'Test sport',
            'supports_online_booking' => true,
            'is_active' => $active,
            'sort_order' => 1,
        ]);
    }

    private function createCourt(Sport $sport, string $name, bool $active): Court
    {
        return Court::query()->create([
            'sport_id' => $sport->id,
            'name' => $name,
            'is_active' => $active,
        ]);
    }
}
