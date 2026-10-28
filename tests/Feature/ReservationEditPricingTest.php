<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Reservations\Pages\ManageReservations;
use App\Models\Court;
use App\Models\Equipment;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use App\Services\AdminReservationService;
use Carbon\Carbon;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationEditPricingTest extends TestCase
{
    use RefreshDatabase;

    private Court $basket;
    private Court $padel;
    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-28 09:00:00'));
        Mail::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $basket = Sport::create(['name' => 'Basket 3x3', 'is_active' => true]);
        $padel = Sport::create(['name' => 'Padel', 'is_active' => true]);
        $this->basket = Court::create(['name' => 'Basket teren', 'sport_id' => $basket->id, 'is_active' => true]);
        $this->padel = Court::create(['name' => 'Padel teren', 'sport_id' => $padel->id, 'is_active' => true]);
        foreach ([$basket, $padel] as $sport) {
            foreach ([['08:00', '16:00', 1000, 1400, 1800], ['16:00', '00:00', 3000, 4500, 6000]] as [$start, $end, $p60, $p90, $p120]) {
                $factor = $sport->id === $padel->id ? 2 : 1;
                PricingRule::create(['sport_id' => $sport->id, 'name' => $start, 'start_time' => $start, 'end_time' => $end,
                    'price_60' => $p60 * $factor, 'price_90' => $p90 * $factor, 'price_120' => $p120 * $factor, 'is_active' => true, 'valid_to' => '2030-12-31']);
            }
        }
        $this->equipment = Equipment::create(['name' => 'Oprema', 'stock_quantity' => 10, 'is_active' => true, 'is_rentable' => true, 'rental_price' => 100]);
    }

    private function booking(array $extra = []): Reservation
    {
        return app(AdminReservationService::class)->create([
            'court_id' => $this->basket->id, 'booking_date' => '2026-12-24', 'booking_time' => '19:00',
            'duration_minutes' => 120, 'customer_type' => 'guest', 'guest_name' => 'Test vremena',
            'equipment' => [['equipment_id' => $this->equipment->id, 'quantity' => 1]], ...$extra,
        ]);
    }

    private function edit(Reservation $record)
    {
        return Livewire::test(ManageReservations::class)->mountAction(TestAction::make('edit')->table($record));
    }

    public function test_duration_updates_end_and_tariff_price_immediately_and_saves_the_same_values(): void
    {
        $record = $this->booking();
        $edit = $this->edit($record);
        foreach ([[60, '20:00:00', 3000], [90, '20:30:00', 4500], [120, '21:00:00', 6000]] as [$minutes, $end, $price]) {
            $edit->set('mountedActions.0.data.duration_minutes', $minutes)
                ->assertSet('mountedActions.0.data.ends_at', '2026-12-24 '.$end)
                ->assertSet('mountedActions.0.data.court_price', (float) $price)
                ->assertSet('mountedActions.0.data.total_price', (float) $price + 100);
        }
        $edit->set('mountedActions.0.data.duration_minutes', 90)->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame(4500.0, (float) $record->fresh()->court_price);
        $this->assertSame(4600.0, (float) $record->fresh()->total_price);
        $this->assertSame('20:30', $record->fresh()->ends_at->format('H:i'));
    }

    public function test_start_time_and_crossing_tariff_boundary_recalculate_price(): void
    {
        $record = $this->booking();
        $this->edit($record)->set('mountedActions.0.data.starts_at', '2026-12-24 14:00:00')
            ->assertSet('mountedActions.0.data.ends_at', '2026-12-24 16:00:00')
            ->assertSet('mountedActions.0.data.court_price', 1800.0)
            ->set('mountedActions.0.data.starts_at', '2026-12-24 15:00:00')
            ->assertSet('mountedActions.0.data.ends_at', '2026-12-24 17:00:00')
            ->assertSet('mountedActions.0.data.court_price', 4000.0)
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame(4100.0, (float) $record->fresh()->total_price);
    }

    public function test_end_time_updates_duration_and_price(): void
    {
        $record = $this->booking();
        $this->edit($record)->set('mountedActions.0.data.ends_at', '2026-12-24 20:30:00')
            ->assertSet('mountedActions.0.data.duration_minutes', 90)
            ->assertSet('mountedActions.0.data.court_price', 4500.0)
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame(90, $record->fresh()->duration_minutes);
    }

    public function test_sport_change_requires_matching_court_then_uses_its_sport_tariff(): void
    {
        $record = $this->booking();
        $this->edit($record)->set('mountedActions.0.data.sport_id', $this->padel->sport_id)
            ->assertSet('mountedActions.0.data.court_id', null)
            ->assertSet('mountedActions.0.data.court_price', null)
            ->set('mountedActions.0.data.court_id', $this->padel->id)
            ->assertSet('mountedActions.0.data.court_price', 12000.0)
            ->assertSet('mountedActions.0.data.total_price', 12100.0)
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame($this->padel->id, $record->fresh()->court_id);
        $this->assertSame(12000.0, (float) $record->fresh()->court_price);
    }

    public function test_save_recalculates_even_if_duration_was_submitted_without_reactive_hooks(): void
    {
        $record = $this->booking();
        Livewire::test(ManageReservations::class)->callAction(TestAction::make('edit')->table($record), data: ['duration_minutes' => 60])
            ->assertHasNoActionErrors();
        $this->assertSame('20:00', $record->fresh()->ends_at->format('H:i'));
        $this->assertSame(3000.0, (float) $record->fresh()->court_price);
        $this->assertSame(3100.0, (float) $record->fresh()->total_price);
    }

    public function test_manual_correction_is_preserved_until_the_schedule_changes_again(): void
    {
        $record = $this->booking();
        $this->edit($record)->set('mountedActions.0.data.duration_minutes', 90)
            ->set('mountedActions.0.data.court_price', 4200)
            ->assertSet('mountedActions.0.data.total_price', 4300.0)
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame(4200.0, (float) $record->fresh()->court_price);
        $this->edit($record->fresh())->set('mountedActions.0.data.admin_note', 'Only a note')
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame(4300.0, (float) $record->fresh()->total_price);
        $this->edit($record->fresh())->set('mountedActions.0.data.duration_minutes', 60)
            ->assertSet('mountedActions.0.data.court_price', 3000.0);
    }

    public function test_unrelated_edit_keeps_existing_price_even_if_current_tariff_is_different(): void
    {
        $record = $this->booking(['court_price_override' => 5777]);
        PricingRule::query()->update(['is_active' => false]);
        $this->edit($record)->set('mountedActions.0.data.admin_note', 'Existing price')
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame(5777.0, (float) $record->fresh()->court_price);
        $this->assertSame(5877.0, (float) $record->fresh()->total_price);
    }

    public function test_missing_tariff_and_invalid_duration_cannot_save_stale_prices(): void
    {
        $record = $this->booking();
        $this->edit($record)->set('mountedActions.0.data.starts_at', '2031-01-02 19:00:00')
            ->assertSet('mountedActions.0.data.court_price', null)
            ->callMountedAction()->assertHasActionErrors(['court_price']);
        $this->edit($record)->set('mountedActions.0.data.ends_at', '2026-12-24 20:15:00')
            ->assertSet('mountedActions.0.data.court_price', null)
            ->callMountedAction()->assertHasActionErrors(['court_price']);
        $this->assertSame(6000.0, (float) $record->fresh()->court_price);
        $this->assertSame('21:00', $record->fresh()->ends_at->format('H:i'));
    }

    public function test_midnight_end_is_kept_on_the_following_day(): void
    {
        $record = $this->booking();
        $this->edit($record)->set('mountedActions.0.data.duration_minutes', 60)
            ->set('mountedActions.0.data.starts_at', '2026-12-24 23:00:00')
            ->assertSet('mountedActions.0.data.ends_at', '2026-12-25 00:00:00')
            ->assertSet('mountedActions.0.data.court_price', 3000.0)
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame('2026-12-25 00:00', $record->fresh()->ends_at->format('Y-m-d H:i'));
    }

    public function test_gift_stays_free_when_rescheduling_and_uses_tariff_when_gift_is_removed(): void
    {
        $record = $this->booking(['is_gift' => true]);
        $this->edit($record)->set('mountedActions.0.data.duration_minutes', 90)
            ->assertSet('mountedActions.0.data.court_price', 0)
            ->assertSet('mountedActions.0.data.total_price', 0)
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame(0.0, (float) $record->fresh()->total_price);
        $this->edit($record->fresh())->set('mountedActions.0.data.is_gift', false)
            ->assertSet('mountedActions.0.data.court_price', 4500.0)
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame(4500.0, (float) $record->fresh()->court_price);
    }

    public function test_toggling_gift_on_then_off_without_saving_keeps_the_equipment_amount(): void
    {
        $record = $this->booking();
        $this->edit($record)->set('mountedActions.0.data.is_gift', true)
            ->assertSet('mountedActions.0.data.total_price', 0)
            ->set('mountedActions.0.data.is_gift', false)
            ->assertSet('mountedActions.0.data.equipment_price', '100.00')
            ->assertSet('mountedActions.0.data.total_price', 6100.0);
    }

    public function test_editing_duration_of_an_opening_promotion_booking_keeps_it_free(): void
    {
        $record = Reservation::create([
            'sport_id' => $this->basket->sport_id, 'court_id' => $this->basket->id, 'guest_name' => 'Promotivni termin',
            'status' => 'reserved', 'starts_at' => '2026-10-16 19:00:00', 'ends_at' => '2026-10-16 21:00:00',
            'duration_minutes' => 120, 'court_price' => 0, 'equipment_price' => 0, 'total_price' => 0,
        ]);
        $this->edit($record)->set('mountedActions.0.data.duration_minutes', 90)
            ->assertSet('mountedActions.0.data.court_price', 0)
            ->assertSet('mountedActions.0.data.total_price', 0)
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame(0.0, (float) $record->fresh()->total_price);
    }
}
