<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Court;
use App\Models\Equipment;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReservationGuardrailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_public_reservation_rejects_unsupported_duration(): void
    {
        [$sport, $court] = $this->createSportCourtAndPricing('Padel', 'padel');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reservations.store'), [
            'court_id' => $court->id,
            'starts_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'duration_minutes' => 180,
            'equipment' => [],
        ]);

        $response->assertSessionHasErrors('duration_minutes');
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_public_reservation_rejects_a_deactivated_court(): void
    {
        [$sport, $court] = $this->createSportCourtAndPricing('Padel', 'padel');
        $court->update(['is_active' => false]);

        $response = $this->actingAs(User::factory()->create())->post(route('reservations.store'), [
            'court_id' => $court->id,
            'starts_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'equipment' => [],
        ]);

        $response->assertSessionHasErrors('court_id');
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_public_reservation_rejects_period_outside_operating_hours(): void
    {
        [$sport, $court] = $this->createSportCourtAndPricing('Padel', 'padel');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reservations.store'), [
            'court_id' => $court->id,
            'starts_at' => now()->addDay()->setTime(22, 30)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'equipment' => [],
        ]);

        $response->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_booking_availability_only_offers_durations_that_end_before_closing(): void
    {
        [$sport] = $this->createSportCourtAndPricing('Padel', 'padel');

        $response = $this->getJson(route('booking.availability', [
            'sport' => $sport->slug,
            'date' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk();

        $lateSlot = collect($response->json('days.0.times'))->firstWhere('time', '22:00');

        $this->assertNotNull($lateSlot);
        $this->assertSame([60], collect($lateSlot['durations'])->pluck('minutes')->all());
    }

    public function test_public_reservation_rejects_equipment_from_another_sport(): void
    {
        [$padel, $court] = $this->createSportCourtAndPricing('Padel', 'padel');
        [$basket] = $this->createSportCourtAndPricing('Basket 3x3', 'basket-3x3');
        $user = User::factory()->create();

        $equipment = $this->createEquipment($basket, stock: 4);

        $response = $this->actingAs($user)->post(route('reservations.store'), [
            'court_id' => $court->id,
            'starts_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'equipment' => [[
                'equipment_id' => $equipment->id,
                'quantity' => 1,
            ]],
        ]);

        $response->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_overlapping_reservations_cannot_exceed_equipment_stock(): void
    {
        [$sport, $firstCourt] = $this->createSportCourtAndPricing('Padel', 'padel');
        $secondCourt = $this->createCourt($sport, 'Padel teren 2', 'padel-teren-2');
        $equipment = $this->createEquipment($sport, stock: 2);
        $startsAt = now()->addDay()->setTime(10, 0);

        $existing = Reservation::query()->create([
            'guest_name' => 'Postojeci gost',
            'guest_phone' => '+38160111000',
            'sport_id' => $sport->id,
            'court_id' => $firstCourt->id,
            'status' => ReservationStatus::Reserved,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'duration_minutes' => 60,
            'court_price' => 2800,
            'equipment_price' => 800,
            'total_price' => 3600,
        ]);
        $existing->equipmentItems()->create([
            'equipment_id' => $equipment->id,
            'quantity' => 2,
            'unit_price' => 400,
            'line_total' => 800,
        ]);

        $response = $this->actingAs(User::factory()->create())->post(route('reservations.store'), [
            'court_id' => $secondCourt->id,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'duration_minutes' => 60,
            'equipment' => [[
                'equipment_id' => $equipment->id,
                'quantity' => 1,
            ]],
        ]);

        $response->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_model_rejects_overlapping_reserved_slots_for_admin_writes(): void
    {
        [$sport, $court] = $this->createSportCourtAndPricing('Padel', 'padel');
        $startsAt = now()->addDay()->setTime(10, 0);

        $this->createReservation($sport, $court, $startsAt);

        $this->expectException(ValidationException::class);

        $this->createReservation($sport, $court, $startsAt->copy()->addMinutes(30));
    }

    public function test_user_cannot_cancel_a_past_reservation_by_direct_request(): void
    {
        [$sport, $court] = $this->createSportCourtAndPricing('Padel', 'padel');
        $user = User::factory()->create();
        $startsAt = now()->subDay()->setTime(10, 0);
        $reservation = $this->createReservation($sport, $court, $startsAt, $user);

        $response = $this->actingAs($user)->post(route('reservations.cancel', $reservation));

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('reservation');
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => ReservationStatus::Reserved->value,
        ]);
    }

    public function test_availability_rejects_invalid_or_past_dates(): void
    {
        [$sport] = $this->createSportCourtAndPricing('Padel', 'padel');

        $this->getJson(route('booking.availability', ['sport' => $sport->slug, 'date' => 'not-a-date']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');

        $this->getJson(route('booking.availability', ['sport' => $sport->slug, 'date' => now()->subDay()->toDateString()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    private function createSportCourtAndPricing(string $name, string $slug): array
    {
        $sport = Sport::query()->create([
            'name' => $name,
            'slug' => $slug,
            'short_description' => "{$name} sport",
            'description' => "{$name} opis",
            'supports_online_booking' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $court = $this->createCourt($sport, "{$name} teren 1", "{$slug}-teren-1");

        PricingRule::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Dnevni blok',
            'days_of_week' => [],
            'start_time' => '08:00:00',
            'end_time' => '23:00:00',
            'price_60' => 2800,
            'price_90' => 4000,
            'price_120' => 5200,
            'is_active' => true,
        ]);

        return [$sport, $court];
    }

    private function createCourt(Sport $sport, string $name, string $slug): Court
    {
        return Court::query()->create([
            'sport_id' => $sport->id,
            'name' => $name,
            'slug' => $slug,
            'location' => 'Hala A',
            'surface' => 'Sinteticka trava',
            'description' => 'Test teren',
            'requires_approval' => false,
            'is_active' => true,
        ]);
    }

    private function createEquipment(Sport $sport, int $stock): Equipment
    {
        return Equipment::query()->create([
            'sport_id' => $sport->id,
            'name' => "{$sport->name} reket",
            'slug' => "{$sport->slug}-reket",
            'sku' => strtoupper($sport->slug).'-001',
            'short_description' => 'Test oprema',
            'description' => 'Test oprema',
            'rental_price' => 400,
            'stock_quantity' => $stock,
            'is_rentable' => true,
            'is_sellable' => false,
            'is_active' => true,
        ]);
    }

    private function createReservation(
        Sport $sport,
        Court $court,
        CarbonInterface $startsAt,
        ?User $user = null,
    ): Reservation {
        return Reservation::query()->create([
            'user_id' => $user?->id,
            'guest_name' => $user ? null : 'Test gost',
            'guest_phone' => $user ? null : '+38160111222',
            'sport_id' => $sport->id,
            'court_id' => $court->id,
            'status' => ReservationStatus::Reserved,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'duration_minutes' => 60,
            'court_price' => 2800,
            'equipment_price' => 0,
            'total_price' => 2800,
        ]);
    }
}
