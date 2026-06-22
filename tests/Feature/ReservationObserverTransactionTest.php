<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Mail\AdminReservationNotificationMail;
use App\Mail\ReservationConfirmedMail;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\Sport;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ReservationObserverTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_and_emails_are_deferred_until_the_reservation_transaction_commits(): void
    {
        Mail::fake();
        Config::set('arena.contact.email', 'info@scarena.rs');

        [$sport, $court] = $this->createSportAndCourt();
        $startsAt = now()->addDay()->setTime(10, 0);
        $cacheKey = 'booking.availability.v2.'.$sport->id.'.'.$startsAt->toDateString();

        Cache::put($cacheKey, ['stale' => true], now()->addMinute());

        DB::beginTransaction();

        try {
            $this->createGuestReservation($sport, $court, $startsAt);

            $this->assertTrue(Cache::has($cacheKey));
            Mail::assertNothingSent();

            DB::commit();
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }

        $this->assertFalse(Cache::has($cacheKey));
        Mail::assertQueued(ReservationConfirmedMail::class);
        Mail::assertQueued(AdminReservationNotificationMail::class);
    }

    public function test_rolled_back_reservation_keeps_cache_and_does_not_send_emails(): void
    {
        Mail::fake();
        Config::set('arena.contact.email', 'info@scarena.rs');

        [$sport, $court] = $this->createSportAndCourt();
        $startsAt = now()->addDay()->setTime(10, 0);
        $cacheKey = 'booking.availability.v2.'.$sport->id.'.'.$startsAt->toDateString();

        Cache::put($cacheKey, ['current' => true], now()->addMinute());

        try {
            DB::transaction(function () use ($sport, $court, $startsAt): void {
                $this->createGuestReservation($sport, $court, $startsAt);

                throw new RuntimeException('Force transaction rollback.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Force transaction rollback.', $exception->getMessage());
        }

        $this->assertTrue(Cache::has($cacheKey));
        $this->assertDatabaseCount('reservations', 0);
        Mail::assertNothingSent();
    }

    private function createSportAndCourt(): array
    {
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

        return [$sport, $court];
    }

    private function createGuestReservation(Sport $sport, Court $court, CarbonInterface $startsAt): Reservation
    {
        return Reservation::query()->create([
            'guest_name' => 'Test gost',
            'guest_phone' => '+38160111222',
            'guest_email' => 'gost@example.com',
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
