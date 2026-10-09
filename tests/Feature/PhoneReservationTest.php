<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Reservations\Pages\ManageReservations;
use App\Filament\Widgets\CalendarReservationsWidget;
use App\Models\Court;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AdminReservationService;
use Database\Seeders\DatabaseSeeder;
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
}
