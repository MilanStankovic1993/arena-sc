<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Reservations\Pages\ManageReservations;
use App\Filament\Widgets\CalendarReservationsWidget;
use App\Mail\AdminReservationNotificationMail;
use App\Mail\ReservationCancelledMail;
use App\Mail\ReservationConfirmedMail;
use App\Mail\ReservationSeriesMail;
use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AdminReservationService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PhoneReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('public');
        config(['arena.seed_admin.email' => '', 'arena.seed_admin.password' => '', 'arena.booking.is_open' => false]);
        $this->seed(DatabaseSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin, 'email_verified_at' => now()]));
    }

    private function data(): array
    {
        return [
            'court_id' => Court::query()->where('slug', 'padel-teren-1')->sole()->id,
            'booking_date' => now()->addDay()->toDateString(),
            'duration_minutes' => 60, 'booking_time' => '23:00',
            'customer_type' => 'guest', 'guest_name' => 'Telefonski gost', 'guest_phone' => '0601234567',
        ];
    }

    public function test_operator_creates_midnight_guest_booking_with_automatic_price_and_equipment(): void
    {
        $equipment = Equipment::query()->where('sku', 'PAT10PCH26')->sole();
        Livewire::test(ManageReservations::class)
            ->callAction('create', data: [...$this->data(), 'equipment' => [['equipment_id' => $equipment->id, 'quantity' => 2]]])
            ->assertHasNoActionErrors();

        $reservation = Reservation::query()->sole();
        $this->assertSame('00:00', $reservation->ends_at->format('H:i'));
        $this->assertSame('Telefonski gost', $reservation->guest_name);
        $this->assertSame(800.0, (float) $reservation->equipment_price);
        $this->assertSame((float) $reservation->court_price + 800, (float) $reservation->total_price);
        $this->assertSame(2, $reservation->equipmentItems()->sole()->quantity);
        $this->assertArrayNotHasKey('23:00', app(AdminReservationService::class)->times($reservation->court_id, $this->data()['booking_date'], 60));
    }

    public function test_phone_booking_rejects_a_slot_taken_after_the_form_was_opened(): void
    {
        $service = app(AdminReservationService::class);
        $service->create($this->data());
        $this->expectException(ValidationException::class);
        $service->create($this->data());
    }

    public function test_calendar_creation_prefills_the_selected_court_and_time(): void
    {
        Livewire::test(CalendarReservationsWidget::class)
            ->callAction('create', data: [
                'guest_name' => 'Gost iz kalendara', 'guest_phone' => '0601234567',
            ], arguments: [
                'court_id' => $this->data()['court_id'],
                'booking_date' => $this->data()['booking_date'],
                'booking_time' => '23:00',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('23:00', Reservation::query()->sole()->starts_at->format('H:i'));
    }

    public function test_registered_customer_and_special_price_are_saved_correctly(): void
    {
        $customer = User::factory()->create();
        Livewire::test(ManageReservations::class)->callAction('create', data: [
            ...$this->data(), 'customer_type' => 'user', 'user_id' => $customer->id,
            'court_price_override' => 2500,
        ])->assertHasNoActionErrors();
        $reservation = Reservation::query()->sole();
        $this->assertSame($customer->id, $reservation->user_id);
        $this->assertNull($reservation->guest_name);
        $this->assertSame(2500.0, (float) $reservation->total_price);
        $this->assertTrue($reservation->participants()->whereKey($customer->id)->exists());
    }

    public function test_missing_guest_phone_prevents_creation(): void
    {
        Livewire::test(ManageReservations::class)->callAction('create', data: [
            ...$this->data(), 'guest_phone' => '',
        ])->assertHasActionErrors(['guest_phone']);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_equipment_stock_failure_does_not_leave_partial_reservation(): void
    {
        $equipment = Equipment::query()->where('sku', 'PAT10PCH26')->sole();
        Livewire::test(ManageReservations::class)->callAction('create', data: [
            ...$this->data(), 'equipment' => [['equipment_id' => $equipment->id, 'quantity' => 11]],
        ])->assertHasActionErrors(['equipment']);
        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseCount('reservation_equipment', 0);
    }

    public function test_changing_duration_resets_selected_time_and_checks_midnight_boundary(): void
    {
        Livewire::test(ManageReservations::class)->mountAction('create')->fillForm($this->data())
            ->fillForm(['duration_minutes' => 90])
            ->assertSchemaStateSet(['booking_time' => null]);
        $data = $this->data();
        $times = app(AdminReservationService::class)->times($data['court_id'], $data['booking_date'], 90);
        $this->assertArrayHasKey('22:30', $times);
        $this->assertArrayNotHasKey('23:00', $times);
    }

    private function recurringData(): array
    {
        $first = now()->next('Thursday')->startOfDay();

        return [...$this->data(),
            'booking_date' => $first->toDateString(), 'duration_minutes' => 90, 'booking_time' => '19:00',
            'repeat_weekly' => true, 'repeat_until' => $first->copy()->addMonthsNoOverflow(3)->toDateString(),
            'guest_email' => 'guest@example.test',
        ];
    }

    public function test_three_month_weekly_guest_series_blocks_every_occurrence_and_sends_summary(): void
    {
        config(['arena.contact.email' => 'admin@example.test']);
        $data = $this->recurringData();
        Livewire::test(ManageReservations::class)->callAction('create', data: $data)->assertHasNoActionErrors();
        $records = Reservation::query()->orderBy('starts_at')->get();
        $this->assertSame(app(AdminReservationService::class)->dates($data)->count(), $records->count());
        $this->assertCount(1, $records->pluck('series_id')->unique());
        $this->assertNotNull($records->first()->series_id);
        foreach ($records as $record) {
            $this->assertSame('Thursday', $record->starts_at->format('l'));
            $this->assertSame('20:30', $record->ends_at->format('H:i'));
            $this->assertArrayNotHasKey('19:00', app(AdminReservationService::class)->times($record->court_id, $record->starts_at->toDateString(), 90));
        }
        Mail::assertQueued(ReservationSeriesMail::class, 2);
        Mail::assertNotQueued(ReservationConfirmedMail::class);
        Mail::assertNotQueued(AdminReservationNotificationMail::class);
        $mail = new ReservationSeriesMail($records);
        $this->assertStringContainsString($records->last()->starts_at->format('d.m.Y'), $mail->render());
    }

    public function test_conflict_requires_explicit_skip_and_does_not_partially_create_series(): void
    {
        $data = $this->recurringData();
        $conflictDate = Carbon::parse($data['booking_date'])->addWeek()->toDateString();
        $service = app(AdminReservationService::class);
        $service->create([...$data, 'repeat_weekly' => false, 'booking_date' => $conflictDate]);
        Livewire::test(ManageReservations::class)->callAction('create', data: $data)->assertHasActionErrors(['skip_dates']);
        $this->assertDatabaseCount('reservations', 1);
        Livewire::test(ManageReservations::class)->callAction('create', data: [...$data, 'skip_dates' => [$conflictDate]])->assertHasNoActionErrors();
        $this->assertSame($service->dates($data)->count() - 1, Reservation::whereNotNull('series_id')->count());
        $this->assertSame(1, Reservation::whereDate('starts_at', $conflictDate)->count());
    }

    public function test_registered_user_series_and_cancel_remaining_preserve_past_and_other_bookings(): void
    {
        $data = $this->recurringData();
        $customer = User::factory()->create(['email' => 'customer@example.test']);
        $service = app(AdminReservationService::class);
        $first = $service->create([...$data, 'customer_type' => 'user', 'user_id' => $customer->id]);
        $this->assertSame($customer->id, $first->user_id);
        $this->assertTrue($first->participants()->whereKey($customer->id)->exists());
        $unrelated = $service->create([...$this->data(), 'booking_date' => $data['booking_date'], 'booking_time' => '10:00']);
        $count = Reservation::where('series_id', $first->series_id)->count();
        $this->travelTo($first->ends_at->copy()->addMinute());
        Mail::fake();
        config(['arena.contact.email' => 'admin@example.test']);
        $this->assertSame($count - 1, $service->cancelRemaining($first));
        $this->assertSame('reserved', $first->fresh()->status->value);
        $this->assertSame('reserved', $unrelated->fresh()->status->value);
        $this->assertSame($count - 1, Reservation::where('series_id', $first->series_id)->where('status', 'cancelled')->count());
        Mail::assertQueued(ReservationSeriesMail::class, 2);
        Mail::assertNotQueued(ReservationCancelledMail::class);
        $this->assertSame(0, $service->cancelRemaining($first));
    }

    public function test_series_preview_detects_court_closure_and_equipment_shortage(): void
    {
        $data = $this->recurringData();
        $equipment = Equipment::where('sku', 'PAT10PCH26')->sole();
        $service = app(AdminReservationService::class);
        $blocked = Carbon::parse($data['booking_date'])->addWeek()->setTime(19, 0);
        CourtClosure::create(['court_id' => $data['court_id'], 'title' => 'Blokada', 'starts_at' => $blocked, 'ends_at' => $blocked->copy()->addHour(), 'is_active' => true]);
        $preview = $service->preview($data);
        $this->assertNotNull($preview->firstWhere('date', $blocked->toDateString())['reason']);
        $preview = $service->preview([...$data, 'equipment' => [['equipment_id' => $equipment->id, 'quantity' => 11]]]);
        $this->assertSame($preview->count(), $preview->whereNotNull('reason')->count());
    }

    public function test_cancelling_one_occurrence_keeps_the_rest_of_the_series_reserved(): void
    {
        $first = app(AdminReservationService::class)->create($this->recurringData());
        $count = Reservation::where('series_id', $first->series_id)->count();
        Livewire::test(ManageReservations::class)
            ->callAction(TestAction::make('cancel_one')->table($first))
            ->assertHasNoActionErrors();
        $this->assertSame('cancelled', $first->fresh()->status->value);
        $this->assertSame($count - 1, Reservation::where('series_id', $first->series_id)->where('status', 'reserved')->count());
    }
}
