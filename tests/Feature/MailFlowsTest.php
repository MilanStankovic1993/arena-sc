<?php

namespace Tests\Feature;

use App\Mail\AdminReservationNotificationMail;
use App\Mail\ContactMessageConfirmationMail;
use App\Mail\ContactMessageReceivedMail;
use App\Mail\ReservationCancelledMail;
use App\Mail\ReservationConfirmedMail;
use App\Models\Court;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_sends_admin_and_customer_emails(): void
    {
        Mail::fake();
        Config::set('arena.contact.email', 'info@scarena.rs');

        $response = $this->post(route('contact.store'), [
            'name' => 'Milan Stankovic',
            'email' => 'milan@example.com',
            'phone' => '0601234567',
            'message' => 'Zelim vise informacija o terminima.',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('status');

        Mail::assertSent(ContactMessageReceivedMail::class, function (ContactMessageReceivedMail $mail) {
            return $mail->hasTo('info@scarena.rs');
        });

        Mail::assertSent(ContactMessageConfirmationMail::class, function (ContactMessageConfirmationMail $mail) {
            return $mail->hasTo('milan@example.com');
        });
    }

    public function test_reservation_creation_sends_customer_and_admin_emails(): void
    {
        Mail::fake();
        Config::set('arena.contact.email', 'info@scarena.rs');

        $user = User::factory()->create();

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
            'capacity' => 4,
            'description' => 'Glavni teren',
            'is_active' => true,
        ]);

        PricingRule::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Dnevni blok',
            'days_of_week' => [],
            'start_time' => '08:00:00',
            'end_time' => '23:30:00',
            'price_60' => 2800,
            'price_90' => 4000,
            'price_120' => 5200,
            'is_active' => true,
        ]);

        $startsAt = now()->addDay()->setTime(10, 0, 0);

        $response = $this
            ->actingAs($user)
            ->post(route('reservations.store'), [
                'court_id' => $court->id,
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'duration_minutes' => 60,
                'customer_note' => 'Test napomena',
                'equipment' => [],
            ]);

        $response->assertRedirect(route('dashboard'));

        Mail::assertSent(ReservationConfirmedMail::class, function (ReservationConfirmedMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        Mail::assertSent(AdminReservationNotificationMail::class, function (AdminReservationNotificationMail $mail) {
            return $mail->mode === 'created' && $mail->hasTo('info@scarena.rs');
        });
    }

    public function test_reservation_cancellation_sends_customer_and_admin_emails(): void
    {
        Mail::fake();
        Config::set('arena.contact.email', 'info@scarena.rs');

        $user = User::factory()->create();

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
            'capacity' => 4,
            'description' => 'Glavni teren',
            'is_active' => true,
        ]);

        $reservation = Reservation::query()->create([
            'user_id' => $user->id,
            'sport_id' => $sport->id,
            'court_id' => $court->id,
            'status' => 'reserved',
            'starts_at' => now()->addDay()->setTime(12, 0, 0),
            'ends_at' => now()->addDay()->setTime(13, 0, 0),
            'duration_minutes' => 60,
            'court_price' => 2800,
            'equipment_price' => 0,
            'total_price' => 2800,
        ]);

        Mail::fake();

        $response = $this
            ->actingAs($user)
            ->post(route('reservations.cancel', $reservation));

        $response->assertRedirect(route('dashboard'));

        Mail::assertSent(ReservationCancelledMail::class, function (ReservationCancelledMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        Mail::assertSent(AdminReservationNotificationMail::class, function (AdminReservationNotificationMail $mail) {
            return $mail->mode === 'cancelled' && $mail->hasTo('info@scarena.rs');
        });
    }
}
