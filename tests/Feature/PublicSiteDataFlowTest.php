<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Equipment;
use App\Models\Event;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteDataFlowTest extends TestCase
{
    use RefreshDatabase;

    private function storageUrl(string $path): string
    {
        return rtrim(config('app.url'), '/') . '/storage/' . ltrim($path, '/');
    }

    public function test_booking_availability_uses_admin_managed_sport_court_pricing_and_equipment_data(): void
    {
        $sport = Sport::query()->create([
            'name' => 'Padel',
            'slug' => 'padel',
            'short_description' => 'Padel sport',
            'description' => 'Padel opis',
            'cover_image' => 'sports/padel-cover.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $court = Court::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel teren 1',
            'slug' => 'padel-teren-1',
            'location' => 'Hala A',
            'surface' => 'Sinteticka trava',
            'capacity' => 4,
            'image' => 'courts/padel-1.jpg',
            'description' => 'Glavni teren',
            'base_price' => 2800,
            'requires_approval' => false,
            'is_active' => true,
        ]);

        PricingRule::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Dnevni blok',
            'days_of_week' => [],
            'start_time' => '08:00:00',
            'end_time' => '23:30:00',
            'price_60' => 2800,
            'price_90' => 4000,
            'price_120' => 5200,
            'is_active' => true,
        ]);

        Equipment::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel reket',
            'slug' => 'padel-reket',
            'sku' => 'PR-001',
            'image' => 'equipment/padel-reket.jpg',
            'short_description' => 'Reket za termin',
            'description' => 'Opis reketa',
            'rental_price' => 400,
            'sale_price' => 6500,
            'stock_quantity' => 4,
            'is_rentable' => true,
            'is_sellable' => true,
            'is_active' => true,
        ]);

        $response = $this->getJson(route('booking.availability', [
            'sport' => $sport->slug,
            'date' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('sport.name', 'Padel')
            ->assertJsonPath('sport.cover_image_url', $this->storageUrl('sports/padel-cover.jpg'))
            ->assertJsonPath('pricingRules.0.name', 'Dnevni blok')
            ->assertJsonPath('pricingRules.0.price60', 2800)
            ->assertJsonPath('equipment.0.name', 'Padel reket')
            ->assertJsonPath('equipment.0.image_url', $this->storageUrl('equipment/padel-reket.jpg'))
            ->assertJsonPath('days.0.times.0.durations.0.courts.0.name', $court->name)
            ->assertJsonPath('days.0.times.0.durations.0.courts.0.image_url', $this->storageUrl('courts/padel-1.jpg'));
    }

    public function test_site_reservation_created_from_public_form_is_immediately_reserved(): void
    {
        $user = User::factory()->create();

        $sport = Sport::query()->create([
            'name' => 'Padel',
            'slug' => 'padel',
            'short_description' => 'Padel sport',
            'description' => 'Padel opis',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $court = Court::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel teren 1',
            'slug' => 'padel-teren-1',
            'location' => 'Hala A',
            'surface' => 'Sinteticka trava',
            'capacity' => 4,
            'description' => 'Glavni teren',
            'base_price' => 2800,
            'requires_approval' => false,
            'is_active' => true,
        ]);

        PricingRule::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Dnevni blok',
            'days_of_week' => [],
            'start_time' => '08:00:00',
            'end_time' => '23:30:00',
            'price_60' => 2800,
            'price_90' => 4000,
            'price_120' => 5200,
            'is_active' => true,
        ]);

        $startsAt = now()->addDay()->setTime(10, 0, 0);

        $response = $this
            ->actingAs($user)
            ->post(route('reservations.store'), [
                'court_id' => $court->id,
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'duration_minutes' => 60,
                'customer_note' => 'Test napomena',
                'equipment' => [],
            ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'sport_id' => $sport->id,
            'court_id' => $court->id,
            'status' => 'reserved',
            'duration_minutes' => 60,
            'court_price' => 2800,
            'total_price' => 2800,
        ]);
    }

    public function test_event_model_exposes_cover_image_url_for_site_usage(): void
    {
        $event = Event::query()->create([
            'title' => 'Padel Liga',
            'slug' => 'padel-liga',
            'type' => 'league',
            'status' => 'registration',
            'location' => 'Arena SC',
            'cover_image' => 'events/padel-liga.jpg',
            'cta_label' => 'Prijavi se',
            'summary' => 'Liga opis',
            'description' => 'Detaljan opis',
            'rules' => 'Pravila',
            'is_featured' => true,
        ]);

        $this->assertSame($this->storageUrl('events/padel-liga.jpg'), $event->cover_image_url);
    }

    public function test_user_can_cancel_reserved_reservation_from_dashboard_flow(): void
    {
        $user = User::factory()->create();

        $sport = Sport::query()->create([
            'name' => 'Padel',
            'slug' => 'padel',
            'short_description' => 'Padel sport',
            'description' => 'Padel opis',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $court = Court::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel teren 1',
            'slug' => 'padel-teren-1',
            'location' => 'Hala A',
            'surface' => 'Sinteticka trava',
            'capacity' => 4,
            'description' => 'Glavni teren',
            'base_price' => 2800,
            'requires_approval' => false,
            'is_active' => true,
        ]);

        $reservation = Reservation::query()->create([
            'user_id' => $user->id,
            'sport_id' => $sport->id,
            'court_id' => $court->id,
            'status' => 'reserved',
            'starts_at' => now()->addDay()->setTime(12, 0, 0),
            'ends_at' => now()->addDay()->setTime(13, 0, 0),
            'duration_minutes' => 60,
            'court_price' => 2800,
            'equipment_price' => 0,
            'total_price' => 2800,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('reservations.cancel', $reservation));

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Otkazano od korisnika.',
        ]);
    }
}
