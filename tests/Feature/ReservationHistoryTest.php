<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\ReservationAudits\Pages\ListReservationAudits;
use App\Filament\Resources\Reservations\Pages\ManageReservations;
use App\Filament\Widgets\OperationsOverview;
use App\Mail\ReservationDeletedMail;
use App\Mail\AdminReservationNotificationMail;
use App\Models\Court;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\ReservationAudit;
use App\Models\User;
use App\Services\AdminAnalyticsService;
use App\Services\AdminReservationService;
use App\Services\ReservationAvailabilityService;
use App\Services\ReservationParticipantService;
use App\Services\ReservationPricingService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ReservationHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-28 09:00:00'));
        config(['arena.seed_admin.email' => '', 'arena.seed_admin.password' => '', 'arena.contact.email' => 'info@scarena.rs']);
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->admin = User::factory()->create(['name' => 'Administrator istorije', 'role' => UserRole::SuperAdmin]);
        $this->actingAs($this->admin);
    }

    private function booking(array $extra = []): Reservation
    {
        return app(AdminReservationService::class)->create([
            'court_id' => Court::where('slug', 'padel-teren-1')->sole()->id,
            'booking_date' => '2026-10-29', 'booking_time' => '12:00', 'duration_minutes' => 60,
            'customer_type' => 'guest', 'guest_name' => 'Gost istorije', 'guest_phone' => '0601234567',
            'guest_email' => 'gost@example.com', ...$extra,
        ]);
    }

    public function test_history_records_actor_timestamp_and_exact_changes_without_duplicate_creation(): void
    {
        $record = $this->booking();
        $created = $record->audits()->sole();
        $this->assertSame('created', $created->event);
        $this->assertSame($this->admin->id, $created->actor_id);
        $this->assertSame('Administrator istorije', $created->actor_name);
        $this->assertSame('2026-10-28 09:00:00', $created->created_at->toDateTimeString());
        $record->update(['guest_name' => 'Ispravljeno ime', 'total_price' => 4200]);
        $updated = $record->audits()->first();
        $this->assertSame('updated', $updated->event);
        $this->assertSame('Gost istorije', $updated->old_values['guest_name']);
        $this->assertSame('Ispravljeno ime', $updated->new_values['guest_name']);
        $this->assertSame(4200, $updated->new_values['total_price']);
        $record->save();
        $this->assertSame(2, $record->audits()->count());
        Mail::assertQueued(AdminReservationNotificationMail::class, 1);
        Livewire::test(ManageReservations::class)->mountAction(TestAction::make('history')->table($record))->assertActionMounted(TestAction::make('history')->table($record));
        $this->assertStringContainsString('Administrator istorije', view('filament.reservations.history', ['audits' => $record->audits()->get()])->render());
        Livewire::test(ListReservationAudits::class)->assertCanSeeTableRecords($record->audits()->get());
    }

    public function test_delete_is_recoverable_releases_court_preserves_details_and_sends_snapshot_mail(): void
    {
        $equipment = Equipment::where('sku', 'PAT10PCH26')->sole();
        $record = $this->booking(['equipment' => [['equipment_id' => $equipment->id, 'quantity' => 2]]]);
        $customer = User::factory()->create();
        app(ReservationParticipantService::class)->attachUsers($record, [$customer]);
        $price = $record->total_price;
        $record->delete();
        $this->assertSoftDeleted($record);
        $this->assertNull(Reservation::find($record->id));
        $this->assertDatabaseHas('reservation_equipment', ['reservation_id' => $record->id, 'quantity' => 2]);
        $this->assertDatabaseHas('reservation_participants', ['reservation_id' => $record->id, 'user_id' => $customer->id]);
        $this->assertTrue(app(ReservationAvailabilityService::class)->isAvailable($record->court, $record->starts_at, $record->ends_at));
        Mail::assertQueued(ReservationDeletedMail::class, fn ($mail) => $mail->hasTo('info@scarena.rs') && $mail->snapshot['id'] === $record->id && $mail->snapshot['actor'] === $this->admin->name);
        Mail::assertQueued(ReservationDeletedMail::class, fn ($mail) => $mail->hasTo('gost@example.com'));
        $deleted = $record->audits()->first();
        $this->assertSame('deleted', $deleted->event);
        $record->restore();
        $this->assertNotSoftDeleted($record);
        $this->assertSame($price, $record->fresh()->total_price);
        $this->assertSame(2, $record->equipmentItems()->sole()->quantity);
        $this->assertSame('restored', $record->audits()->first()->event);
        $this->assertFalse(app(ReservationAvailabilityService::class)->isAvailable($record->court, $record->starts_at, $record->ends_at));
        Mail::assertQueued(AdminReservationNotificationMail::class, 1);
        $mail = new ReservationDeletedMail(['id' => $record->id, 'customer' => 'Originalni gost', 'phone' => '', 'sport' => 'Padel', 'court' => 'Teren', 'starts_at' => '29.10.2026 12:00', 'ends_at' => '13:00', 'total_price' => 3000, 'actor' => 'Admin', 'deleted_at' => '28.10.2026 09:00']);
        $this->assertStringContainsString('Originalni gost', unserialize(serialize($mail))->render());
    }

    public function test_restore_rejects_a_conflicting_booking_and_keeps_deleted_record_and_audit_intact(): void
    {
        $record = $this->booking();
        $record->delete();
        $other = $this->booking(['guest_name' => 'Novi gost']);
        $count = $record->audits()->count();
        Livewire::test(ManageReservations::class)->filterTable('record_state', 'deleted')
            ->callAction(TestAction::make('restore')->table($record))->assertHasActionErrors(['reservation']);
        $this->assertSoftDeleted($record);
        $this->assertSame($count, $record->audits()->count());
        $this->assertNotSoftDeleted($other);
    }

    public function test_deleted_equipment_is_available_and_restore_rejects_insufficient_stock(): void
    {
        $equipment = Equipment::where('sku', 'PAT10PCH26')->sole();
        $equipment->update(['stock_quantity' => 1]);
        $data = ['equipment' => [['equipment_id' => $equipment->id, 'quantity' => 1]]];
        $record = $this->booking($data);
        $record->delete();
        $otherCourt = Court::where('slug', 'padel-teren-2')->sole();
        $this->booking([...$data, 'court_id' => $otherCourt->id]);
        try {
            $record->restore();
            $this->fail('Unavailable equipment must block restoration.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reservation', $exception->errors());
        }
        $this->assertSoftDeleted($record);
    }

    public function test_deleted_bookings_are_excluded_from_statistics_dashboard_and_person_totals(): void
    {
        $record = $this->booking(['court_price_override' => 4321]);
        $record->delete();
        $snapshot = app(AdminAnalyticsService::class)->periodSnapshot(['preset' => 'all']);
        $this->assertSame(0, $snapshot['total']);
        $this->assertSame(0.0, $snapshot['futureValue']);
        Livewire::test(OperationsOverview::class)->assertDontSee('4.321 RSD');
        Livewire::test(ManageReservations::class)->assertCanNotSeeTableRecords([$record])
            ->filterTable('record_state', 'deleted')->assertCanSeeTableRecords([$record])->assertSee('0,00 RSD');
        Livewire::test(ManageReservations::class)->filterTable('record_state', 'deleted')
            ->callAction(TestAction::make('restore')->table($record))->assertHasNoActionErrors();
        $this->assertSame(4321.0, app(AdminAnalyticsService::class)->periodSnapshot(['preset' => 'all'])['futureValue']);
    }

    public function test_list_delete_and_bulk_delete_keep_recoverable_rows_and_audits(): void
    {
        $one = $this->booking();
        $two = $this->booking(['booking_date' => '2026-10-30']);
        Livewire::test(ManageReservations::class)->callAction(TestAction::make('delete')->table($one))->assertHasNoActionErrors();
        Livewire::test(ManageReservations::class)->selectTableRecords([$two])->callAction(TestAction::make('delete')->table()->bulk())->assertHasNoActionErrors();
        $this->assertSoftDeleted($one);
        $this->assertSoftDeleted($two);
        $this->assertSame(2, ReservationAudit::where('event', 'deleted')->count());
    }

    public function test_rollback_does_not_delete_log_or_send_deletion_mail(): void
    {
        $record = $this->booking();
        try {
            DB::transaction(function () use ($record): void {
                $record->delete();
                throw new RuntimeException('Rollback');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback', $exception->getMessage());
        }
        $this->assertNotSoftDeleted($record);
        $this->assertSame(0, $record->audits()->where('event', 'deleted')->count());
        Mail::assertNotQueued(ReservationDeletedMail::class);
    }

    public function test_guest_and_customer_cannot_access_admin_history_or_delete_actions(): void
    {
        $record = $this->booking();
        auth()->logout();
        $this->get('/admin/reservation-audits')->assertRedirect('/admin/login');
        $this->delete('/rezervacije/'.$record->id)->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => UserRole::Customer]));
        $this->get('/admin/reservation-audits')->assertForbidden();
        $this->assertNotSoftDeleted($record);
    }

    public function test_equipment_and_participant_edits_record_before_after_values(): void
    {
        $equipment = Equipment::where('sku', 'PAT10PCH26')->sole();
        $record = $this->booking(['equipment' => [['equipment_id' => $equipment->id, 'quantity' => 1]]]);
        $record->equipmentItems()->sole()->update(['quantity' => 2]);
        $audit = $record->audits()->first();
        $this->assertSame('equipment_updated', $audit->event);
        $this->assertSame(1, $audit->old_values['equipment']['quantity']);
        $this->assertSame(2, $audit->new_values['equipment']['quantity']);
        $user = User::factory()->create();
        app(ReservationParticipantService::class)->syncUsers($record, [$user]);
        $this->assertSame([$user->id], $record->audits()->first()->new_values['participant_ids']);
    }

    public function test_repeated_delete_does_not_send_duplicate_mail_and_force_delete_is_blocked(): void
    {
        $record = $this->booking(['guest_email' => null]);
        $stale = $record->fresh();
        $record->delete();
        $this->assertFalse($stale->delete());
        Mail::assertQueued(ReservationDeletedMail::class, 1);
        $this->expectException(ValidationException::class);
        $record->forceDelete();
    }

    public function test_stale_edit_cannot_change_an_already_deleted_booking(): void
    {
        $record = $this->booking();
        $stale = $record->fresh();
        $record->delete();
        try {
            $stale->update(['guest_name' => 'Should not persist']);
            $this->fail('Stale changes must be blocked.');
        } catch (ValidationException) {
            $this->assertSame('Gost istorije', Reservation::withTrashed()->findOrFail($record->id)->guest_name);
        }
    }

    public function test_gift_and_cancelled_bookings_restore_without_changing_price_or_status(): void
    {
        $gift = $this->booking(['is_gift' => true]);
        $gift->delete();
        $gift->restore();
        $this->assertTrue($gift->fresh()->is_gift);
        $this->assertSame(0.0, (float) $gift->fresh()->total_price);
        $gift->update(['status' => 'cancelled']);
        $gift->delete();
        $this->booking();
        $gift->restore();
        $this->assertSame('cancelled', $gift->fresh()->status->value);
        $this->assertSame(0.0, (float) $gift->fresh()->total_price);
    }

    public function test_public_booking_records_guest_actor_without_an_account(): void
    {
        auth()->logout();
        $court = Court::where('slug', 'padel-teren-1')->sole();
        $this->post('/rezervacije', [
            'court_id' => $court->id, 'starts_at' => '2026-10-29 12:00:00', 'duration_minutes' => 60,
            'guest_name' => 'Javni gost', 'guest_phone' => '0609999999',
        ])->assertRedirect();
        $record = Reservation::sole();
        $audit = $record->audits()->sole();
        $this->assertSame('guest', $audit->actor_type);
        $this->assertNull($audit->actor_id);
        $this->assertSame('Javni gost', $audit->actor_name);
    }

    public function test_delete_and_restore_recalculate_dashboard_and_all_time_statistics_for_every_time_bucket(): void
    {
        $records = [
            ['record' => $this->booking(['booking_date' => '2026-10-29', 'court_price_override' => 4321]), 'now' => '2026-10-29 11:00', 'count' => 'future', 'value' => 'futureValue', 'dashboardCount' => 'Budući termini', 'dashboardValue' => 'Vrednost budućih rezervacija'],
            ['record' => $this->booking(['booking_date' => '2026-10-30', 'court_price_override' => 4500]), 'now' => '2026-10-30 12:30', 'count' => 'ongoing', 'value' => 'ongoingValue', 'dashboardCount' => 'Termini u toku', 'dashboardValue' => 'Vrednost termina u toku'],
            ['record' => $this->booking(['booking_date' => '2026-10-31', 'court_price_override' => 6000]), 'now' => '2026-10-31 14:00', 'count' => 'completed', 'value' => 'revenue', 'dashboardCount' => 'Odigrani termini', 'dashboardValue' => 'Prihod od odigranih termina'],
        ];
        $dashboard = fn (): array => (new class extends OperationsOverview {
            public function values(): array
            {
                return collect($this->getStats())->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()])->all();
            }
        })->values();
        $service = app(AdminAnalyticsService::class);
        foreach ($records as $case) {
            $this->travelTo(Carbon::parse($case['now']));
            $beforeStats = $service->periodSnapshot(['preset' => 'all']);
            $beforeDashboard = $dashboard();
            $case['record']->delete();
            $afterStats = $service->periodSnapshot(['preset' => 'all']);
            $afterDashboard = $dashboard();
            $this->assertSame($beforeStats['total'] - 1, $afterStats['total']);
            $this->assertSame($beforeStats[$case['count']] - 1, $afterStats[$case['count']]);
            $this->assertSame($beforeStats[$case['value']] - (float) $case['record']->total_price, $afterStats[$case['value']]);
            $this->assertSame((string) ((int) $beforeDashboard[$case['dashboardCount']] - 1), $afterDashboard[$case['dashboardCount']]);
            $this->assertSame(number_format($afterStats[$case['value']], 0, ',', '.').' RSD', $afterDashboard[$case['dashboardValue']]);
            $this->assertSame($afterStats['completed'] > 0 ? $afterStats['revenue'] / $afterStats['completed'] : 0, $afterStats['averagePrice']);
            $case['record']->restore();
            $this->assertSame($beforeStats, $service->periodSnapshot(['preset' => 'all']));
            $this->assertSame($beforeDashboard, $dashboard());
        }
    }
}
