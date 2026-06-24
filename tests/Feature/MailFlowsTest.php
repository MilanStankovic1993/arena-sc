<?php

namespace Tests\Feature;

use App\Mail\AdminReservationNotificationMail;
use App\Mail\ContactMessageConfirmationMail;
use App\Mail\ContactMessageReceivedMail;
use App\Mail\MembershipActivatedMail;
use App\Mail\MembershipExpiringSoonMail;
use App\Mail\ReservationCancelledMail;
use App\Mail\ReservationConfirmedMail;
use App\Models\Court;
use App\Models\MembershipPlan;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use App\Models\UserMembership;
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

        Mail::assertQueued(ContactMessageReceivedMail::class, function (ContactMessageReceivedMail $mail) {
            return $mail->hasTo('info@scarena.rs');
        });

        Mail::assertQueued(ContactMessageConfirmationMail::class, function (ContactMessageConfirmationMail $mail) {
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

        Mail::assertQueued(ReservationConfirmedMail::class, function (ReservationConfirmedMail $mail) use ($startsAt, $user) {
            $expectedTerm = '10:00 - 11:00 ('.$startsAt->format('d.m.Y').')';

            return $mail->hasTo($user->email)
                && str_contains($mail->render(), $expectedTerm);
        });

        Mail::assertQueued(AdminReservationNotificationMail::class, function (AdminReservationNotificationMail $mail) use ($startsAt) {
            return $mail->mode === 'created'
                && $mail->hasTo('info@scarena.rs')
                && str_contains($mail->render(), '10:00 - 11:00 ('.$startsAt->format('d.m.Y').')');
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

        Mail::assertQueued(ReservationCancelledMail::class, function (ReservationCancelledMail $mail) use ($reservation, $user) {
            $expectedTerm = '12:00 - 13:00 ('.$reservation->starts_at->format('d.m.Y').')';

            return $mail->hasTo($user->email)
                && str_contains($mail->render(), $expectedTerm);
        });

        Mail::assertQueued(AdminReservationNotificationMail::class, function (AdminReservationNotificationMail $mail) use ($reservation) {
            $expectedTerm = '12:00 - 13:00 ('.$reservation->starts_at->format('d.m.Y').')';

            return $mail->mode === 'cancelled'
                && $mail->hasTo('info@scarena.rs')
                && str_contains($mail->render(), $expectedTerm);
        });
    }

    public function test_membership_activation_sends_customer_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'clan@example.com']);
        $sport = Sport::query()->create([
            'name' => 'Padel',
            'slug' => 'padel',
            'short_description' => 'Padel sport',
            'description' => 'Padel opis',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $plan = MembershipPlan::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel mesec',
            'period_label' => 'Mesec dana',
            'duration_days' => 30,
            'reservation_limit' => 3,
            'price' => 12000,
            'is_active' => true,
        ]);

        UserMembership::query()->create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(29)->toDateString(),
            'is_active' => true,
        ]);

        Mail::assertSent(MembershipActivatedMail::class, function (MembershipActivatedMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->membership->membershipPlan->name === 'Padel mesec';
        });
    }

    public function test_membership_expiry_command_sends_single_reminder_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'istek@example.com']);
        $sport = Sport::query()->create([
            'name' => 'Padel',
            'slug' => 'padel',
            'short_description' => 'Padel sport',
            'description' => 'Padel opis',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $plan = MembershipPlan::query()->create([
            'sport_id' => $sport->id,
            'name' => 'Padel mesec',
            'period_label' => 'Mesec dana',
            'duration_days' => 30,
            'reservation_limit' => 3,
            'price' => 12000,
            'is_active' => true,
        ]);

        $membership = UserMembership::query()->create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'starts_at' => now()->subDays(27)->toDateString(),
            'ends_at' => now()->addDays(3)->toDateString(),
            'is_active' => true,
        ]);

        Mail::fake();

        $this->artisan('memberships:send-expiry-reminders')
            ->expectsOutput('Poslato podsetnika za istek članarine: 1.')
            ->assertExitCode(0);

        Mail::assertSent(MembershipExpiringSoonMail::class, function (MembershipExpiringSoonMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->daysBeforeExpiry === 3;
        });

        $this->assertNotNull($membership->fresh()->last_expiry_reminder_sent_at);

        Mail::fake();

        $this->artisan('memberships:send-expiry-reminders')
            ->expectsOutput('Poslato podsetnika za istek članarine: 0.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }
}
