<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\Event;
use App\Models\EventEntry;
use App\Models\EventMatch;
use App\Models\MembershipPlan;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\ReservationEquipment;
use App\Models\Sport;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DomainInvariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_pricing_rule_rejects_invalid_values_and_allows_inactive_drafts_to_overlap(): void
    {
        $sport = $this->createSport();
        $active = $this->createPricingRule($sport);

        $draft = PricingRule::query()->create([
            ...$active->only([
                'sport_id',
                'days_of_week',
                'start_time',
                'end_time',
                'price_60',
                'price_90',
                'price_120',
            ]),
            'name' => 'Nacrt cenovnika',
            'is_active' => false,
        ]);

        $this->assertFalse($draft->is_active);
        $this->assertValidationError(
            fn () => $draft->update(['is_active' => true]),
            'start_time',
        );

        $this->assertValidationErrors(function () use ($sport): void {
            PricingRule::query()->create([
                'sport_id' => $sport->id,
                'name' => 'Neispravan cenovnik',
                'days_of_week' => [0],
                'start_time' => '20:00:00',
                'end_time' => '10:00:00',
                'price_60' => -1,
                'price_90' => 100,
                'price_120' => 100,
                'valid_from' => '2026-08-10',
                'valid_to' => '2026-08-01',
                'is_active' => false,
            ]);
        }, ['end_time', 'price_60', 'valid_to']);
    }

    public function test_domain_models_reject_reversed_periods_and_negative_plan_values(): void
    {
        $sport = $this->createSport();
        $court = $this->createCourt($sport);

        $this->assertValidationError(fn () => Event::query()->create([
            'title' => 'Pogresan dogadjaj',
            'type' => 'tournament',
            'status' => 'draft',
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-01',
        ]), 'end_date');

        $this->assertValidationError(fn () => CourtClosure::query()->create([
            'court_id' => $court->id,
            'title' => 'Pogresna blokada',
            'starts_at' => now()->addDay()->setTime(12, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'is_active' => true,
        ]), 'ends_at');

        $this->assertValidationErrors(fn () => MembershipPlan::query()->create([
            'name' => 'Pogresna clanarina',
            'duration_days' => 0,
            'reservation_limit' => 0,
            'price' => -1,
            'is_active' => false,
        ]), ['duration_days', 'reservation_limit', 'price']);
    }

    public function test_event_match_rejects_entries_from_another_event_and_missing_finished_score(): void
    {
        $firstEvent = $this->createEvent('Prvi turnir');
        $secondEvent = $this->createEvent('Drugi turnir');
        $firstEntry = $this->createEventEntry($firstEvent, 'Tim A');
        $secondEntry = $this->createEventEntry($secondEvent, 'Tim B');

        $this->assertValidationError(fn () => EventMatch::query()->create([
            'event_id' => $firstEvent->id,
            'home_entry_id' => $firstEntry->id,
            'away_entry_id' => $secondEntry->id,
            'status' => 'scheduled',
        ]), 'away_entry_id');

        $this->assertValidationError(fn () => EventMatch::query()->create([
            'event_id' => $firstEvent->id,
            'home_entry_id' => $firstEntry->id,
            'status' => 'finished',
        ]), 'home_score');
    }

    public function test_reservation_and_equipment_reject_negative_or_zero_financial_values(): void
    {
        $sport = $this->createSport();
        $court = $this->createCourt($sport);
        $startsAt = now()->addDay()->setTime(10, 0);

        $this->assertValidationError(fn () => Reservation::query()->create([
            'guest_name' => 'Test gost',
            'guest_phone' => '+38160111222',
            'sport_id' => $sport->id,
            'court_id' => $court->id,
            'status' => ReservationStatus::Reserved,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'duration_minutes' => 60,
            'court_price' => -1,
            'equipment_price' => 0,
            'total_price' => -1,
        ]), 'court_price');

        $item = new ReservationEquipment([
            'quantity' => 0,
            'unit_price' => -1,
            'line_total' => -1,
        ]);

        $this->assertValidationErrors(fn () => $item->save(), ['quantity', 'unit_price', 'line_total']);
    }

    private function createSport(): Sport
    {
        return Sport::query()->create([
            'name' => 'Padel',
            'short_description' => 'Test sport',
            'supports_online_booking' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createCourt(Sport $sport): Court
    {
        return Court::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel teren',
            'is_active' => true,
        ]);
    }

    private function createPricingRule(Sport $sport): PricingRule
    {
        return PricingRule::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Aktivni cenovnik',
            'days_of_week' => [],
            'start_time' => '08:00:00',
            'end_time' => '23:00:00',
            'price_60' => 2000,
            'price_90' => 3000,
            'price_120' => 4000,
            'is_active' => true,
        ]);
    }

    private function createEvent(string $title): Event
    {
        return Event::query()->create([
            'title' => $title,
            'type' => 'tournament',
            'status' => 'draft',
        ]);
    }

    private function createEventEntry(Event $event, string $teamName): EventEntry
    {
        return EventEntry::query()->create([
            'event_id' => $event->id,
            'user_id' => User::factory()->create()->id,
            'team_name' => $teamName,
        ]);
    }

    private function assertValidationError(Closure $callback, string $field): void
    {
        $this->assertValidationErrors($callback, [$field]);
    }

    private function assertValidationErrors(Closure $callback, array $fields): void
    {
        try {
            $callback();
            $this->fail('Ocekivana je validaciona greska.');
        } catch (ValidationException $exception) {
            foreach ($fields as $field) {
                $this->assertArrayHasKey($field, $exception->errors());
            }
        }
    }
}
