<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Reservations\Pages\ManageReservations;
use App\Filament\Widgets\OperationsOverview;
use App\Models\Court;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AdminAnalyticsService;
use App\Services\AdminReservationService;
use App\Services\ReservationAvailabilityService;
use App\Services\ReservationListService;
use App\Services\ReservationPricingService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class GiftReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-26 14:00:00'));
        Mail::fake();
        config(['arena.seed_admin.email' => '', 'arena.seed_admin.password' => '']);
        $this->seed(DatabaseSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
    }

    private function data(array $extra = []): array
    {
        return [
            'court_id' => Court::where('slug', 'padel-teren-1')->sole()->id,
            'booking_date' => '2026-10-27', 'duration_minutes' => 60, 'booking_time' => '12:00',
            'customer_type' => 'guest', 'guest_name' => 'Poklon gost', 'guest_phone' => '0601234567',
            ...$extra,
        ];
    }

    public function test_create_gift_from_form_is_zero_including_equipment_and_still_blocks_the_slot(): void
    {
        $equipment = Equipment::where('sku', 'PAT10PCH26')->sole();
        Livewire::test(ManageReservations::class)->callAction('create', data: $this->data([
            'is_gift' => true, 'court_price_override' => 9500,
            'equipment' => [['equipment_id' => $equipment->id, 'quantity' => 2]],
        ]))->assertHasNoActionErrors();
        $record = Reservation::sole();
        $this->assertTrue($record->is_gift);
        $this->assertSame(ReservationStatus::Reserved, $record->status);
        foreach (['court_price', 'equipment_price', 'total_price'] as $field) {
            $this->assertSame(0.0, (float) $record->$field);
        }
        $item = $record->equipmentItems()->sole();
        $this->assertSame(2, $item->quantity);
        $this->assertSame(0.0, (float) $item->unit_price);
        $this->assertSame(0.0, (float) $item->line_total);
        $this->assertFalse(app(ReservationAvailabilityService::class)->isAvailable($record->court, $record->starts_at, $record->ends_at));
        Livewire::test(ManageReservations::class)->filterTable('is_gift', 'yes')->assertSee('Rezervisana · Poklon');
        $record->update(['total_price' => 10000, 'court_price' => 10000]);
        $this->assertSame(0.0, (float) $record->fresh()->total_price);
        $item->update(['unit_price' => 400, 'line_total' => 800]);
        $this->assertSame(0.0, (float) $item->fresh()->line_total);
    }

    public function test_gift_action_affects_only_selected_booking_and_cancel_releases_its_slot(): void
    {
        $service = app(AdminReservationService::class);
        $first = $service->create($this->data());
        $second = $service->create($this->data(['booking_date' => '2026-10-28']));
        $secondPrice = $second->total_price;
        Livewire::test(ManageReservations::class)->callAction(TestAction::make('gift')->table($first))->assertHasNoActionErrors();
        $this->assertTrue($first->fresh()->is_gift);
        $this->assertFalse($second->fresh()->is_gift);
        $this->assertSame($secondPrice, $second->fresh()->total_price);
        Livewire::test(ManageReservations::class)->callAction(TestAction::make('cancel_one')->table($first))->assertHasNoActionErrors();
        $first->refresh();
        $this->assertSame(ReservationStatus::Cancelled, $first->status);
        $this->assertTrue($first->is_gift);
        $this->assertSame('Otkazana · Poklon', $first->display_status);
        $this->assertTrue(app(ReservationAvailabilityService::class)->isAvailable($first->court, $first->starts_at, $first->ends_at));
    }

    public function test_gifts_are_excluded_from_revenue_counts_average_charts_dashboard_and_person_sums(): void
    {
        $service = app(AdminReservationService::class);
        $gift = $service->create($this->data(['is_gift' => true]));
        $paid = $service->create($this->data(['booking_date' => '2026-10-28', 'court_price_override' => 4500]));
        $pastGift = $service->create($this->data(['is_gift' => true, 'booking_date' => '2026-10-29']));
        $pastPaid = $service->create($this->data(['booking_date' => '2026-10-30', 'court_price_override' => 3000]));
        // Even an inconsistent legacy/direct SQL price must not turn a gift into revenue.
        DB::table('reservations')->where('id', $gift->id)->update(['total_price' => 99999]);
        DB::table('reservations')->where('id', $pastGift->id)->update(['starts_at' => '2026-10-25 12:00:00', 'ends_at' => '2026-10-25 13:00:00', 'total_price' => 99999]);
        DB::table('reservations')->where('id', $pastPaid->id)->update(['starts_at' => '2026-10-25 14:00:00', 'ends_at' => '2026-10-25 15:00:00']);
        $analytics = app(AdminAnalyticsService::class);
        $all = ['preset' => 'all'];
        $snapshot = $analytics->periodSnapshot($all);
        $this->assertSame(3000.0, $snapshot['revenue']);
        $this->assertSame(4500.0, $snapshot['futureValue']);
        $this->assertSame(3000.0, $snapshot['averagePrice']);
        $this->assertSame(1, $snapshot['completed']);
        $this->assertSame(2, $analytics->reservationQuery($all)->count());
        $this->assertSame(3000.0, (float) array_sum($analytics->monthlyRevenue($all)['data']));
        $this->assertSame(4500.0, (float) $analytics->courtPerformance($all)->sum('futureValue'));
        $list = app(ReservationListService::class);
        $group = $list->paginateGroups($list->query(Reservation::query()), 10, 1, 'page')->first();
        $this->assertSame(4, $group->customer_group_count);
        $this->assertSame(3000.0, $group->customer_group_completed_revenue);
        $this->assertSame(4500.0, $group->customer_group_future_value);
        Livewire::test(OperationsOverview::class)->assertSee('3.000 RSD')->assertSee('4.500 RSD')->assertDontSee('99.999');
    }

    public function test_recurring_gift_preview_and_saved_series_are_free_without_changing_prices(): void
    {
        $service = app(AdminReservationService::class);
        $data = $this->data(['is_gift' => true, 'repeat_weekly' => true, 'repeat_until' => '2026-11-10']);
        $preview = $service->preview($data);
        $this->assertCount(3, $preview);
        $this->assertEquals(0, $preview->sum('price'));
        $first = $service->create($data);
        $this->assertSame(3, Reservation::where('series_id', $first->series_id)->where('is_gift', true)->count());
        $this->assertEquals(0, Reservation::sum('total_price'));
        $paid = $service->create($this->data(['booking_date' => '2026-10-28']));
        $this->assertGreaterThan(0, (float) $paid->total_price);
    }

    public function test_edit_can_mark_existing_booking_as_gift_and_equipment_remains_reserved(): void
    {
        $equipment = Equipment::where('sku', 'PAT10PCH26')->sole();
        $equipment->update(['stock_quantity' => 2]);
        $record = app(AdminReservationService::class)->create($this->data([
            'equipment' => [['equipment_id' => $equipment->id, 'quantity' => 2]],
        ]));
        Livewire::test(ManageReservations::class)
            ->callAction(TestAction::make('edit')->table($record), data: ['is_gift' => true])
            ->assertHasNoActionErrors();
        $record->refresh();
        $this->assertTrue($record->is_gift);
        $this->assertSame(0.0, (float) $record->total_price);
        $this->assertSame(0.0, (float) $record->equipmentItems()->sole()->line_total);
        $this->assertSame(2, $record->equipmentItems()->sole()->quantity);
        $this->expectException(RuntimeException::class);
        app(ReservationPricingService::class)->hydrateEquipmentPricing(
            [['equipment_id' => $equipment->id, 'quantity' => 1]],
            $record->sport_id,
            $record->starts_at,
            $record->ends_at,
        );
    }
}
