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
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationEquipmentEditTest extends TestCase
{
    use RefreshDatabase;

    private Court $court;

    private Court $otherCourt;

    private Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
        $sport = Sport::create(['name' => 'Padel', 'is_active' => true]);
        $this->court = Court::create(['name' => 'Prvi', 'sport_id' => $sport->id, 'is_active' => true]);
        $this->otherCourt = Court::create(['name' => 'Drugi', 'sport_id' => $sport->id, 'is_active' => true]);
        PricingRule::create(['name' => 'Cena', 'sport_id' => $sport->id, 'is_active' => true,
            'start_time' => '08:00', 'end_time' => '00:00', 'price_60' => 1000, 'price_90' => 1500, 'price_120' => 2000]);
        $this->equipment = Equipment::create(['name' => 'Reket', 'sport_id' => $sport->id, 'stock_quantity' => 2,
            'is_active' => true, 'is_rentable' => true, 'rental_price' => 100]);
    }

    private function booking(?Court $court = null, string $time = '13:00'): Reservation
    {
        return app(AdminReservationService::class)->create([
            'court_id' => ($court ?? $this->court)->id, 'booking_date' => now()->addDays(2)->toDateString(),
            'booking_time' => $time, 'duration_minutes' => 60, 'customer_type' => 'guest',
            'guest_name' => 'Test gost', 'guest_phone' => '060000000',
            'equipment' => [['equipment_id' => $this->equipment->id, 'quantity' => 1]],
        ]);
    }

    private function edit(Reservation $record)
    {
        return Livewire::test(ManageReservations::class)->mountAction(TestAction::make('edit')->table($record));
    }

    private function item(Reservation $record): string
    {
        return 'record-'.$record->equipmentItems()->sole()->id;
    }

    public function test_excess_quantity_is_rejected_without_partial_parent_or_item_changes(): void
    {
        $record = $this->booking();
        $this->edit($record)
            ->set('mountedActions.0.data.equipmentItems.'.$this->item($record).'.quantity', 3)
            ->set('mountedActions.0.data.total_price', 1300)
            ->set('mountedActions.0.data.admin_note', 'Must not persist')
            ->callMountedAction()->assertHasActionErrors(['equipmentItems']);
        $this->assertSame(1, $record->equipmentItems()->sole()->quantity);
        $this->assertSame(1100.0, (float) $record->fresh()->total_price);
        $this->assertNull($record->fresh()->admin_note);
    }

    public function test_other_courts_share_the_same_stock_and_current_booking_is_not_double_counted(): void
    {
        $record = $this->booking();
        $this->booking($this->otherCourt);
        $this->edit($record)->set('mountedActions.0.data.admin_note', 'Allowed')
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame('Allowed', $record->fresh()->admin_note);
        $this->edit($record)->set('mountedActions.0.data.equipmentItems.'.$this->item($record).'.quantity', 2)
            ->callMountedAction()->assertHasActionErrors(['equipmentItems']);
        $this->assertSame(1, $record->equipmentItems()->sole()->quantity);
    }

    public function test_duplicate_equipment_lines_are_aggregated_before_saving(): void
    {
        $record = $this->booking();
        $this->booking($this->otherCourt);
        $this->edit($record)->set('mountedActions.0.data.equipmentItems.extra', [
            'equipment_id' => $this->equipment->id, 'quantity' => 1, 'unit_price' => 100, 'line_total' => 100,
        ])->callMountedAction()->assertHasActionErrors(['equipmentItems']);
        $this->assertSame(1, $record->equipmentItems()->count());
    }

    public function test_rescheduling_rechecks_stock_for_the_new_period(): void
    {
        $record = $this->booking($this->court, '15:00');
        $other = $this->booking($this->otherCourt);
        $other->equipmentItems()->sole()->update(['quantity' => 2]);
        $this->edit($record)->set('mountedActions.0.data.starts_at', $other->starts_at->toDateTimeString())
            ->set('mountedActions.0.data.ends_at', $other->ends_at->toDateTimeString())
            ->callMountedAction()->assertHasActionErrors(['equipmentItems']);
        $this->assertSame('15:00', $record->fresh()->starts_at->format('H:i'));
    }

    public function test_reactivating_cancelled_booking_cannot_overbook_equipment(): void
    {
        $record = $this->booking();
        $record->update(['status' => 'cancelled']);
        $other = $this->booking($this->otherCourt);
        $other->equipmentItems()->sole()->update(['quantity' => 2]);
        $this->edit($record)->set('mountedActions.0.data.status', 'reserved')
            ->callMountedAction()->assertHasActionErrors(['equipmentItems']);
        $this->assertSame('cancelled', $record->fresh()->status->value);
    }

    public function test_non_overlapping_bookings_do_not_reduce_stock_and_manual_prices_are_preserved(): void
    {
        $record = $this->booking();
        $this->booking($this->otherCourt, '14:00');
        $this->edit($record)->set('mountedActions.0.data.equipmentItems.'.$this->item($record).'.quantity', 2)
            ->set('mountedActions.0.data.equipmentItems.'.$this->item($record).'.unit_price', 50)
            ->set('mountedActions.0.data.equipmentItems.'.$this->item($record).'.line_total', 100)
            ->callMountedAction()->assertHasNoActionErrors();
        $item = $record->equipmentItems()->sole();
        $this->assertSame(2, $item->quantity);
        $this->assertSame(50.0, (float) $item->unit_price);
    }

    public function test_cancelled_history_does_not_reserve_stock(): void
    {
        $record = $this->booking();
        $record->update(['status' => 'cancelled']);
        $this->equipment->update(['stock_quantity' => 0]);
        $this->edit($record)->set('mountedActions.0.data.admin_note', 'History only')
            ->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame('History only', $record->fresh()->admin_note);
    }

    public function test_fractional_equipment_quantities_are_rejected(): void
    {
        $record = $this->booking();
        $this->edit($record)->set('mountedActions.0.data.equipmentItems.'.$this->item($record).'.quantity', 1.5)
            ->callMountedAction()->assertHasActionErrors(['equipmentItems.'.$this->item($record).'.quantity']);
        $this->assertSame(1, $record->equipmentItems()->sole()->quantity);
    }
}
