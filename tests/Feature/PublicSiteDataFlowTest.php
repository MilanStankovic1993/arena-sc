<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Equipment;
use App\Models\Event;
use App\Models\MembershipPlan;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use App\Models\UserMembership;
use App\Services\AdminAnalyticsService;
use App\Services\ReservationParticipantService;
use App\Services\UserStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicSiteDataFlowTest extends TestCase
{
    use RefreshDatabase;

    private function storageUrl(string $path): string
    {
        return rtrim(config('app.url'), '/') . '/storage/' . ltrim($path, '/');
    }

    public function test_price_list_page_uses_admin_managed_pricing_and_membership_data(): void
    {
        $sport = Sport::query()->create([
            'name' => 'Padel',
            'slug' => 'padel',
            'short_description' => 'Padel sport',
            'description' => 'Padel opis',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PricingRule::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Dnevni blok',
            'days_of_week' => [],
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'price_60' => 3000,
            'price_90' => 4200,
            'price_120' => 5400,
            'is_active' => true,
        ]);

        MembershipPlan::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Mesecna članarina',
            'period_label' => 'Mesec dana',
            'duration_days' => 30,
            'reservation_limit' => 3,
            'price' => 12000,
            'short_description' => 'Tri termina ukupno.',
            'is_active' => true,
        ]);

        $response = $this->get(route('price-list.index'));

        $response->assertOk()
            ->assertSee('Dnevni blok')
            ->assertSee('Mesecna članarina')
            ->assertSee('3 termina ukupno');
    }

    public function test_equipment_page_uses_admin_managed_equipment_data(): void
    {
        $sport = Sport::query()->create([
            'name' => 'Padel',
            'slug' => 'padel',
            'short_description' => 'Padel sport',
            'description' => 'Padel opis',
            'is_active' => true,
            'sort_order' => 1,
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

        $response = $this->get(route('equipment.index'));

        $response->assertOk()
            ->assertSee('Padel reket')
            ->assertSee('Reket za termin')
            ->assertSee('400 RSD')
            ->assertSee('6.500 RSD');
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

        $this->assertDatabaseHas('reservation_participants', [
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_attached_participants_are_counted_in_user_statistics(): void
    {
        Mail::fake();

        $booker = User::factory()->create(['name' => 'Rezervise Termin']);
        $participant = User::factory()->create(['name' => 'Igra Termin']);

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
            'description' => 'Glavni teren',
            'requires_approval' => false,
            'is_active' => true,
        ]);

        $reservation = Reservation::query()->create([
            'user_id' => $booker->id,
            'sport_id' => $sport->id,
            'court_id' => $court->id,
            'status' => 'reserved',
            'starts_at' => now()->addDay()->setTime(18, 0, 0),
            'ends_at' => now()->addDay()->setTime(19, 30, 0),
            'duration_minutes' => 90,
            'court_price' => 4000,
            'equipment_price' => 0,
            'total_price' => 4000,
        ]);

        app(ReservationParticipantService::class)->attachUsers($reservation, [$participant]);

        $this->assertDatabaseHas('reservation_participants', [
            'reservation_id' => $reservation->id,
            'user_id' => $booker->id,
        ]);

        $this->assertDatabaseHas('reservation_participants', [
            'reservation_id' => $reservation->id,
            'user_id' => $participant->id,
        ]);

        $this->assertSame(1, $booker->fresh()->total_reservations);
        $this->assertSame(1, $participant->fresh()->total_reservations);

        $bookerStats = app(UserStatisticsService::class)->summary($booker->fresh());
        $stats = app(UserStatisticsService::class)->summary($participant->fresh());

        $this->assertSame(1, $bookerStats['owned']);
        $this->assertSame(0, $bookerStats['joined']);
        $this->assertSame(1, $stats['total']);
        $this->assertSame(0, $stats['owned']);
        $this->assertSame(1, $stats['joined']);
        $this->assertSame(90, $stats['durationMinutes']);
        $this->assertSame('Padel (1)', $stats['favoriteSport']);
        $this->assertSame('Padel teren 1 (1)', $stats['favoriteCourt']);
    }

    public function test_admin_analytics_filter_counts_participating_user_without_multiplying_system_revenue(): void
    {
        Mail::fake();

        $booker = User::factory()->create();
        $participant = User::factory()->create();
        $otherUser = User::factory()->create();

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
            'description' => 'Glavni teren',
            'requires_approval' => false,
            'is_active' => true,
        ]);

        $reservation = Reservation::query()->create([
            'user_id' => $booker->id,
            'sport_id' => $sport->id,
            'court_id' => $court->id,
            'status' => 'reserved',
            'starts_at' => now()->subDay()->setTime(18, 0, 0),
            'ends_at' => now()->subDay()->setTime(19, 30, 0),
            'duration_minutes' => 90,
            'court_price' => 4000,
            'equipment_price' => 0,
            'total_price' => 4000,
        ]);

        app(ReservationParticipantService::class)->attachUsers($reservation, [$participant]);

        $service = app(AdminAnalyticsService::class);
        $system = $service->periodSnapshot(['preset' => 'all']);
        $filtered = $service->periodSnapshot(['preset' => 'all', 'userId' => $participant->id]);
        $empty = $service->periodSnapshot(['preset' => 'all', 'userId' => $otherUser->id]);

        $this->assertSame(1, $system['total']);
        $this->assertSame(4000.0, (float) $system['revenue']);
        $this->assertSame(2, $system['participantVisits']);
        $this->assertSame(2, $system['uniqueParticipants']);

        $this->assertSame(1, $filtered['total']);
        $this->assertSame(4000.0, (float) $filtered['revenue']);
        $this->assertSame(1, $filtered['participantVisits']);
        $this->assertSame(1, $filtered['uniqueParticipants']);

        $this->assertSame(0, $empty['total']);
        $this->assertSame(0, $empty['participantVisits']);
    }

    public function test_active_membership_limits_total_reservations_for_matching_sport(): void
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
            'description' => 'Glavni teren',
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

        $plan = MembershipPlan::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel mesec',
            'period_label' => 'Mesec dana',
            'duration_days' => 30,
            'reservation_limit' => 1,
            'price' => 12000,
            'is_active' => true,
        ]);

        UserMembership::query()->create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'starts_at' => now()->startOfWeek()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $firstStart = now()->addWeek()->startOfWeek()->addDay()->setTime(10, 0, 0);
        $secondStart = $firstStart->copy()->addWeek()->setTime(11, 0, 0);

        $this
            ->actingAs($user)
            ->post(route('reservations.store'), [
                'court_id' => $court->id,
                'starts_at' => $firstStart->format('Y-m-d H:i:s'),
                'duration_minutes' => 60,
                'customer_note' => null,
                'equipment' => [],
            ])
            ->assertRedirect(route('dashboard'));

        $this
            ->actingAs($user)
            ->post(route('reservations.store'), [
                'court_id' => $court->id,
                'starts_at' => $secondStart->format('Y-m-d H:i:s'),
                'duration_minutes' => 60,
                'customer_note' => null,
                'equipment' => [],
            ])
            ->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_dashboard_displays_active_membership_valid_until_date(): void
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

        $plan = MembershipPlan::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel mesec',
            'period_label' => 'Mesec dana',
            'duration_days' => 30,
            'reservation_limit' => 2,
            'price' => 12000,
            'is_active' => true,
        ]);

        UserMembership::query()->create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDays(20)->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Padel mesec')
            ->assertSee('Do ' . now()->addDays(20)->format('d.m.Y'))
            ->assertSee('2 termina ukupno');
    }

    public function test_booking_availability_requires_pricing_rule_for_public_slots(): void
    {
        $sport = Sport::query()->create([
            'name' => 'Padel',
            'slug' => 'padel',
            'short_description' => 'Padel sport',
            'description' => 'Padel opis',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Court::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel teren 1',
            'slug' => 'padel-teren-1',
            'location' => 'Hala A',
            'surface' => 'Sinteticka trava',
            'capacity' => 4,
            'description' => 'Glavni teren',
            'requires_approval' => false,
            'is_active' => true,
        ]);

        $response = $this->getJson(route('booking.availability', [
            'sport' => $sport->slug,
            'date' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('pricingRules', [])
            ->assertJsonPath('days.0.times', []);
    }

    public function test_site_reservation_requires_pricing_rule_before_creating_reservation(): void
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
            'requires_approval' => false,
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

        $response->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_non_bookable_sport_returns_contact_only_booking_payload(): void
    {
        $sport = Sport::query()->create([
            'name' => 'Pikado',
            'slug' => 'pikado',
            'short_description' => 'Pikado termin',
            'description' => 'Pikado opis',
            'supports_online_booking' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $response = $this->getJson(route('booking.availability', [
            'sport' => $sport->slug,
            'date' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('sport.name', 'Pikado')
            ->assertJsonPath('sport.supports_online_booking', false)
            ->assertJsonPath('contact_only', true)
            ->assertJsonPath('pricingRules', [])
            ->assertJsonPath('days', [])
            ->assertJsonPath('equipment', []);
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
