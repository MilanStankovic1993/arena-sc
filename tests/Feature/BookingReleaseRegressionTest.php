<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\PricingRules\Pages\ManagePricingRules;
use App\Filament\Resources\Reservations\Pages\ManageReservations;
use App\Filament\Widgets\CalendarReservationsWidget;
use App\Models\Court;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AdminReservationService;
use App\Services\ReservationPricingService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class BookingReleaseRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-22 12:00'));
        Mail::fake();
        config(['arena.seed_admin.email' => '', 'arena.seed_admin.password' => '', 'arena.booking.is_open' => true]);
        $this->seed(DatabaseSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin, 'email_verified_at' => now()]));
    }

    public function test_admin_calendar_creates_booking_across_new_year_without_contact_details(): void
    {
        $court = Court::where('slug', 'padel-teren-1')->sole();
        $this->assertArrayHasKey('23:00', app(AdminReservationService::class)->times($court->id, '2026-12-31', 60));
        $component = Livewire::test(CalendarReservationsWidget::class)
            ->mountAction('create', arguments: ['court_id' => $court->id, 'booking_date' => '2026-12-31', 'booking_time' => '23:00'])
            ->assertSchemaStateSet(['booking_date' => '2026-12-31', 'booking_time' => '23:00'])
            ->fillForm(['guest_name' => 'Gost bez kontakta'])
            ->assertSchemaStateSet(['booking_date' => '2026-12-31', 'booking_time' => '23:00'])
            ->callMountedAction();
        $component->assertHasNoActionErrors();
        $record = Reservation::sole();
        $this->assertSame('2027-01-01 00:00', $record->ends_at->format('Y-m-d H:i'));
        $this->assertNull($record->guest_phone);
        $this->assertNull($record->guest_email);
        $data = [
            'court_id' => $court->id, 'booking_date' => '2027-01-07', 'duration_minutes' => 60,
            'booking_time' => '19:00', 'customer_type' => 'guest', 'guest_name' => 'Serija bez kontakta',
            'repeat_weekly' => true, 'repeat_until' => '2027-01-21',
        ];
        Livewire::test(ManageReservations::class)->callAction('create', data: $data)->assertHasNoActionErrors();
        $this->assertSame(3, Reservation::whereNotNull('series_id')->count());
    }

    public function test_future_availability_and_admin_times_include_year_end_and_next_year(): void
    {
        $court = Court::where('slug', 'basket-3x3-teren')->sole();
        foreach (['2026-12-26', '2026-12-31', '2027-01-01', '2027-12-31'] as $date) {
            $this->assertArrayHasKey('23:00', app(AdminReservationService::class)->times($court->id, $date, 60));
            $payload = $this->getJson(route('booking.availability', ['sport' => 'basket-3x3', 'date' => $date]))->assertOk()->json();
            $this->assertSame($date, $payload['days'][0]['date']);
            $this->assertNotEmpty($payload['days'][0]['times']);
        }
    }

    public function test_admin_can_edit_basket_morning_and_evening_prices_independently(): void
    {
        $court = Court::where('slug', 'basket-3x3-teren')->sole();
        $morning = PricingRule::where('name', 'Basket radni dan pre podne')->sole();
        $evening = PricingRule::where('name', 'Basket radni dan popodne')->sole();
        $morning->update(['end_time' => '17:00:00']);
        $evening->update(['start_time' => '17:00:00']);
        Livewire::test(ManagePricingRules::class)->callAction(TestAction::make('edit')->table($morning), data: [
            'price_60' => 2000, 'price_90' => 2800, 'price_120' => 3800,
        ])->assertHasNoActionErrors();
        Livewire::test(ManagePricingRules::class)->callAction(TestAction::make('edit')->table($evening), data: [
            'price_60' => 3000, 'price_90' => 4200, 'price_120' => 5500,
        ])->assertHasNoActionErrors();
        $service = app(ReservationPricingService::class);
        foreach ([['15:00', 90, 2800.0], ['17:00', 90, 4200.0], ['16:30', 90, 4000.0], ['23:00', 60, 3000.0]] as [$time, $minutes, $price]) {
            $start = Carbon::parse('2026-10-22 '.$time);
            $this->assertSame($price, $service->calculateCourtPrice($court, $start, $start->copy()->addMinutes($minutes)));
        }
    }

    public function test_free_adjacent_price_periods_remain_bookable_across_boundary(): void
    {
        $court = Court::where('slug', 'basket-3x3-teren')->sole();
        PricingRule::where('sport_id', $court->sport_id)->update(['price_60' => 0, 'price_90' => 0, 'price_120' => 0]);
        $start = Carbon::parse('2026-10-22 17:30');
        $this->assertSame(0.0, app(ReservationPricingService::class)->calculateCourtPrice($court, $start, $start->copy()->addMinutes(90)));
        $this->assertArrayHasKey('17:30', app(AdminReservationService::class)->times($court->id, '2026-10-22', 90));
    }
}
