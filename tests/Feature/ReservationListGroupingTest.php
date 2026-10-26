<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Reservations\Pages\ManageReservations;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationListGroupingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-22 14:00:00'));
        Mail::fake();
        config(['arena.seed_admin.email' => '', 'arena.seed_admin.password' => '']);
        $this->seed(DatabaseSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));
    }

    private function booking(array $attributes = []): Reservation
    {
        $court = Court::where('slug', 'padel-teren-1')->sole();

        return Reservation::withoutEvents(fn () => Reservation::create([
            'sport_id' => $court->sport_id, 'court_id' => $court->id,
            'guest_name' => 'Luka Janic', 'guest_phone' => '0601234567',
            'status' => 'reserved', 'starts_at' => '2026-10-23 16:00:00', 'ends_at' => '2026-10-23 17:00:00',
            'duration_minutes' => 60, 'court_price' => 3000, 'equipment_price' => 0, 'total_price' => 3000,
            ...$attributes,
        ]));
    }

    public function test_partial_name_search_finds_guest_and_account_bookings_without_merging_same_names(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->booking($i % 2 ? ['guest_name' => 'luka janic', 'guest_phone' => '060 123-4567'] : []);
        }
        $this->booking(['guest_phone' => '0609999999']);
        $user = User::factory()->create(['name' => 'Luka Janic', 'role' => 'customer']);
        $this->booking(['user_id' => $user->id, 'guest_name' => null, 'guest_phone' => null]);
        $this->booking(['guest_name' => 'Marko Petrovic']);
        $page = Livewire::test(ManageReservations::class)->searchTable('luka');
        $records = $page->instance()->getTableRecords();
        $this->assertSame(3, $records->total());
        $this->assertCount(22, $records->items());
        $this->assertCount(3, $records->getCollection()->groupBy('reservation_customer_group'));
        $this->assertSame(20, $records->getCollection()->max('customer_group_count'));
        $this->assertTrue($page->instance()->getTable()->areGroupsCollapsedByDefault());
        preg_match_all('/class="[^"]*fi-ta-group-header-row[^"]*"/', $page->html(), $groupHeaders);
        $this->assertCount(3, $groupHeaders[0]);
        $page->assertSee('20 rezervacija u izabranom pregledu')->assertDontSee('Marko Petrovic');
    }

    public function test_pagination_counts_people_and_never_splits_a_person_across_pages(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->booking(['guest_name' => 'Z Luka']);
        }
        for ($i = 0; $i < 13; $i++) {
            $this->booking(['guest_name' => 'A Gost '.$i]);
        }
        $page = Livewire::test(ManageReservations::class);
        $first = $page->instance()->getTableRecords();
        $this->assertSame(14, $first->total());
        $this->assertSame(10, $first->perPage());
        $this->assertSame(10, $first->lastItem());
        $this->assertCount(10, $first->getCollection()->groupBy('reservation_customer_group'));
        $this->assertCount(20, $first->getCollection()->where('guest_name', 'Z Luka'));
        $this->assertSame('Z Luka', $first->first()->guest_name);
        $this->assertSame('A Gost 0', $first->getCollection()->unique('reservation_customer_group')->values()[1]->guest_name);
        $this->assertLessThan(strpos($page->html(), 'A Gost 0 ·'), strpos($page->html(), 'Z Luka ·'));
        $page->call('gotoPage', 2);
        $second = $page->instance()->getTableRecords();
        $this->assertCount(4, $second->items());
        $this->assertCount(0, $second->getCollection()->where('guest_name', 'Z Luka'));
        $this->assertSame(14, $second->lastItem());
    }

    public function test_phone_email_and_filters_work_with_grouping_and_keep_free_cancelled_records_available(): void
    {
        $paid = $this->booking(['series_id' => 'weekly-test']);
        $free = $this->booking(['guest_name' => 'Promo Gost', 'guest_email' => 'promo@example.test', 'total_price' => 0, 'court_price' => 0, 'status' => 'cancelled']);
        $this->booking(['guest_name' => 'Prosli Gost', 'starts_at' => '2026-10-21 12:00:00', 'ends_at' => '2026-10-21 13:00:00']);
        $this->assertCount(3, Livewire::test(ManageReservations::class)->searchTable('060 123 4567')->instance()->getTableRecords()->items());
        $email = Livewire::test(ManageReservations::class)->searchTable('promo@example.test')->instance()->getTableRecords();
        $this->assertSame($free->id, $email->first()->id);
        $filters = Livewire::test(ManageReservations::class)->instance()->getTable()->getFilters();
        $this->assertSame(['status', 'is_gift', 'sport', 'time_state', 'period'], array_keys($filters));
        $future = Livewire::test(ManageReservations::class)->filterTable('time_state', 'future')->instance()->getTableRecords();
        $this->assertCount(1, $future->items());
        $this->assertSame($paid->id, $future->first()->id);
        $cancelled = Livewire::test(ManageReservations::class)->filterTable('status', 'cancelled')->instance()->getTableRecords();
        $this->assertSame($free->id, $cancelled->first()->id);
        $period = Livewire::test(ManageReservations::class)->filterTable('period', ['from' => '2026-10-21', 'until' => '2026-10-21'])->instance()->getTableRecords();
        $this->assertCount(1, $period->items());
        $this->assertSame('Prosli Gost', $period->first()->guest_name);
    }

    public function test_individual_cancel_still_changes_only_the_selected_reservation(): void
    {
        $first = $this->booking();
        $second = $this->booking();
        $page = Livewire::test(ManageReservations::class)->searchTable('luka');
        $this->assertSame(6000.0, $page->instance()->getTableRecords()->first()->customer_group_future_value);
        $page
            ->callAction(TestAction::make('cancel_one')->table($first))->assertHasNoActionErrors();
        $this->assertSame(3000.0, $page->instance()->getTableRecords()->first()->customer_group_future_value);
        $this->assertDatabaseHas('reservations', ['id' => $first->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('reservations', ['id' => $second->id, 'status' => 'reserved']);
        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_group_amounts_match_completed_and_future_rules_and_respect_filters(): void
    {
        $this->booking(['starts_at' => '2026-10-22 13:00:00', 'ends_at' => '2026-10-22 14:00:00', 'total_price' => 4500]);
        $this->booking(['starts_at' => '2026-10-22 14:00:00', 'ends_at' => '2026-10-22 15:00:00', 'total_price' => 6000]);
        $this->booking(['starts_at' => '2026-10-22 13:30:00', 'ends_at' => '2026-10-22 14:30:00', 'total_price' => 1000]);
        $this->booking(['starts_at' => '2026-10-18 12:00:00', 'ends_at' => '2026-10-18 13:00:00', 'total_price' => 0]);
        $this->booking(['status' => 'cancelled', 'total_price' => 9000]);
        $this->booking(['status' => 'cancelled', 'starts_at' => '2026-10-21 12:00:00', 'ends_at' => '2026-10-21 13:00:00', 'total_price' => 9000]);
        $this->booking(['guest_phone' => '0609999999', 'total_price' => 12000]);
        $page = Livewire::test(ManageReservations::class)->searchTable('luka');
        $record = $page->instance()->getTableRecords()->getCollection()->firstWhere('guest_phone', '0601234567');
        $this->assertSame(6, $record->customer_group_count);
        $this->assertSame(4500.0, $record->customer_group_completed_revenue);
        $this->assertSame(6000.0, $record->customer_group_future_value);
        $page->assertSee('Prihod od odigranih:')->assertSee('4.500,00 RSD')->assertSee('6.000,00 RSD');
        $filtered = Livewire::test(ManageReservations::class)->searchTable('0601234567')->filterTable('time_state', 'future')->instance()->getTableRecords()->first();
        $this->assertSame(1, $filtered->customer_group_count);
        $this->assertSame(0.0, $filtered->customer_group_completed_revenue);
        $this->assertSame(6000.0, $filtered->customer_group_future_value);
    }
}
