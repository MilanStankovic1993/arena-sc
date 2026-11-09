<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\GroupMatches\Pages\ListGroupMatches;
use App\Mail\GroupMatchMail;
use App\Models\Court;
use App\Models\GroupMatch;
use App\Models\GroupMatchPlayer;
use App\Models\Reservation;
use App\Models\Sport;
use App\Models\User;
use App\Services\GroupMatchService;
use Carbon\Carbon;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GroupMatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-06 15:00:00'));
        config(['arena.seed_admin.email' => null, 'arena.seed_admin.password' => null]);
        $this->seed();
        Mail::fake();
    }

    public function test_guest_padel_team_appears_after_email_confirmation_and_reserves_once_full(): void
    {
        $sport = Sport::query()->where('slug', 'padel')->firstOrFail();
        $slot = ['sport_id' => $sport->id, 'starts_at' => '2026-11-06T18:00', 'duration_minutes' => 60];
        $this->get(route('booking.index'))->assertOk()->assertSee('Pronađi ekipu');
        $this->get(route('group-matches.create', $slot))->assertOk()->assertSee('Promeni termin')
            ->assertSee('Nivo igre')->assertSee('3.500,00 RSD za ceo teren');
        $this->post(route('group-matches.store'), $slot + $this->player('organizer') + ['quoted_price' => 3500, 'has_team' => 1])
            ->assertRedirectToRoute('group-matches.index');
        $match = GroupMatch::query()->firstOrFail();
        $this->get(route('group-matches.index'))->assertOk()->assertDontSee($match->id);

        $organizer = $match->players()->firstOrFail();
        $this->post(route('group-matches.player', $organizer), ['action' => 'confirm'])->assertForbidden();
        $this->get($organizer->signedUrl())->assertOk()->assertSee('Potvrdi moje mesto');
        $this->assertNull($organizer->fresh()->confirmed_at);
        $this->post($organizer->signedUrl(), ['action' => 'confirm'])->assertRedirect();
        $this->get($organizer->signedUrl())->assertOk()->assertSee('Pošalji saigračima link');
        $this->get(route('group-matches.index'))->assertOk()->assertSee('1/4 igrača')->assertSee('Kreiraj novi grupni termin');

        $this->post(route('group-matches.join', $match), $this->player('partner') + ['side' => 'A'])->assertSessionHasErrors('side');
        $this->post(route('group-matches.join', $match), $this->player('partner') + ['side' => 'A', 'invite' => $match->team_invite])
            ->assertSessionHasNoErrors();
        $this->post(route('group-matches.join', $match), $this->player('opponent-one') + ['side' => 'B'])->assertSessionHasNoErrors();
        $this->post(route('group-matches.join', $match), $this->player('opponent-two') + ['side' => 'B'])->assertSessionHasNoErrors();
        $this->assertSame(1, $match->confirmedPlayers()->count());
        $this->assertDatabaseCount('reservations', 0);

        $court = Court::query()->whereBelongsTo($sport)->firstOrFail();
        $this->post(route('reservations.store'), [
            'court_id' => $court->id, 'starts_at' => $slot['starts_at'], 'duration_minutes' => 60,
            'guest_name' => 'Redovna ekipa', 'guest_phone' => '0601234567', 'guest_email' => 'regular@example.test',
        ])->assertSessionHasNoErrors();
        foreach ($match->players()->where('organizer', false)->get() as $player) {
            $this->post($player->signedUrl(), ['action' => 'confirm'])->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->assertSame(GroupMatch::Ready, $match->fresh()->status);
        $this->assertDatabaseCount('reservations', 1);

        app(GroupMatchService::class)->reconcile();
        app(GroupMatchService::class)->reconcile();
        $reservation = $match->fresh()->reservation;
        $this->assertNotNull($reservation);
        $this->assertNotEquals($court->id, $reservation->court_id);
        $this->assertEquals(4, $reservation->players_count);
        $this->assertDatabaseCount('reservations', 2);
        $this->assertDatabaseCount('users', 0);
        $this->assertCount(4, Mail::queued(GroupMatchMail::class, fn (GroupMatchMail $mail) => $mail->kind === 'booked'));
        $this->get(route('group-matches.show', $match))->assertOk()->assertSee($reservation->court->name)
            ->assertDontSee('organizer@example.test')->assertDontSee('opponent-one@example.test');
        $this->get($organizer->signedUrl())->assertOk()->assertSee('3.500,00 RSD za ceo teren');
    }

    public function test_group_picker_shows_only_slots_that_fit_the_group_and_confirmation_deadline(): void
    {
        $basket = Sport::query()->where('slug', 'basket-3x3')->sole();
        $date = now()->toDateString();
        $base = ['sport' => $basket->slug, 'date' => $date];
        $this->get(route('group-matches.create', $base))->assertOk()
            ->assertSee('data-day-list', false)->assertSee('data-time-list', false)
            ->assertDontSee('datetime-local')->assertDontSee(route('reservations.store'));

        $regular = collect($this->getJson(route('booking.availability', $base))->assertOk()->json('days.0.times'));
        $group = collect($this->getJson(route('booking.availability', $base + ['group' => 1]))->assertOk()->json('days.0.times'));
        $this->assertTrue($regular->contains('time', '15:30'));
        $this->assertFalse($group->contains('time', '15:30'));
        $this->assertTrue($group->contains('time', '16:00'));
        $this->assertEquals([60], array_column($group->firstWhere('time', '23:00')['durations'], 'minutes'));

        $invalidQuote = route('group-matches.create', ['sport_id' => $basket->id, 'starts_at' => $date.'T15:30', 'duration_minutes' => 60]);
        $this->followingRedirects()->get($invalidQuote)->assertOk()
            ->assertSee('Izaberi termin koji počinje za više od 30 minuta.')
            ->assertDontSee('data-flash-dialog role=', false);

        Court::query()->whereBelongsTo($basket)->update(['capacity' => 5]);
        $this->getJson(route('booking.availability', $base + ['group' => 1]))->assertOk()->assertJsonPath('days.0.times', []);
        $this->assertNotEmpty($this->getJson(route('booking.availability', $base))->assertOk()->json('days.0.times'));
    }

    public function test_basket_shows_the_court_price_and_survives_withdrawal_of_a_waiting_player(): void
    {
        $basket = Sport::query()->where('slug', 'basket-3x3')->firstOrFail();
        $slot = ['sport_id' => $basket->id, 'starts_at' => '2026-11-07T18:00', 'duration_minutes' => 60];
        $price = '3.500,00 RSD za ceo teren';
        $this->get(route('group-matches.create', $slot))->assertOk()->assertSee($price);
        $this->post(route('group-matches.store'), $slot + $this->player('owner') + ['quoted_price' => 3500])
            ->assertRedirectToRoute('group-matches.index');
        $match = GroupMatch::query()->firstOrFail();
        $this->post($match->players()->firstOrFail()->signedUrl(), ['action' => 'confirm'])->assertSessionHasNoErrors();
        $this->get(route('group-matches.index'))->assertOk()->assertSee($price);
        $this->get(route('group-matches.show', ['match' => $match, 'strana' => 'B', 'mesto' => 1]))
            ->assertOk()->assertSeeInOrder(['Prijavi se za igru', $price])->assertDontSee('583,33');

        $waiting = array_replace($this->player('waiting'), ['self_rating' => 10, 'side' => 'B']);
        $this->post(route('group-matches.join', $match), $waiting)->assertSessionHasNoErrors();
        $waitingPlayer = $match->players()->where('email', $waiting['email'])->firstOrFail();
        $this->post($waitingPlayer->signedUrl(), ['action' => 'confirm'])->assertSessionHasNoErrors();
        $this->assertSame(GroupMatchPlayer::LevelReviewPending, $waitingPlayer->fresh()->level_review_status);

        $guests = [];
        foreach (['A', 'A', 'B', 'B'] as $index => $side) {
            $guests[] = $this->player('guest-'.$index) + ['side' => $side];
        }
        $this->post(route('group-matches.join', $match), $this->player('captain') + [
            'side' => 'B', 'group_questionnaire' => 1, 'guests' => $guests,
        ])->assertSessionHasNoErrors();
        foreach ($match->players()->where('organizer', false)->whereKeyNot($waitingPlayer->id)->get() as $player) {
            $this->get($player->signedUrl())->assertOk()->assertSee($price)->assertSee('Potvrdi moje mesto');
            $this->post($player->signedUrl(), ['action' => 'confirm'])->assertSessionHasNoErrors();
        }
        $this->assertSame(GroupMatch::Ready, $match->fresh()->status);
        $this->post($waitingPlayer->signedUrl(), ['action' => 'leave'])->assertRedirectToRoute('group-matches.index');
        $this->assertSame(GroupMatch::Ready, $match->fresh()->status);

        $this->artisan('group-matches:reconcile')->assertSuccessful();
        $this->artisan('group-matches:reconcile')->assertSuccessful();
        $reservation = $match->fresh()->reservation;
        $this->assertNotNull($reservation);
        $this->assertSame('3500.00', $reservation->total_price);
        $this->assertDatabaseCount('reservations', 1);

        $mails = Mail::queued(GroupMatchMail::class, fn (GroupMatchMail $mail) => $mail->kind === 'booked');
        $this->assertCount(6, $mails);
        foreach ($mails as $mail) {
            $this->assertStringContainsString('Ekipa se dogovara kako deli troškove.', $mail->render());
            $this->get($mail->player->signedUrl())->assertOk()->assertSee($price)->assertDontSee('Tvoj iznos za plaćanje');
        }

        $reservation->update(['status' => ReservationStatus::Cancelled]);
        $this->get(route('group-matches.show', $match))->assertOk()->assertSee('Otkazana')
            ->assertDontSee($price)->assertDontSee('Ko igra');
        $this->get($mails->first()->player->signedUrl())->assertOk()->assertSee('Termin više nije aktivan')
            ->assertDontSee('Tvoj iznos za plaćanje');
        $this->assertCount(6, Mail::queued(GroupMatchMail::class, fn (GroupMatchMail $mail) => $mail->kind === 'cancelled'));
    }

    public function test_two_complete_basket_teams_need_superadmin_when_they_share_one_court(): void
    {
        $basket = Sport::query()->where('slug', 'basket-3x3')->firstOrFail();
        $first = $this->fullMatch($basket, 'first');
        $second = $this->fullMatch($basket, 'second');

        app(GroupMatchService::class)->reconcile();
        $this->assertSame(GroupMatch::Review, $first->fresh()->status);
        $this->assertSame(GroupMatch::Review, $second->fresh()->status);
        $this->assertDatabaseCount('reservations', 0);

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->get('/admin/grupni-termini')->assertRedirect();
        $this->actingAs($admin)->get('/admin/grupni-termini')->assertOk()->assertSee('Grupni termini');
        app(GroupMatchService::class)->approve($second, $admin);
        $this->assertSame(GroupMatch::Booked, $second->fresh()->status);
        $this->assertSame(GroupMatch::Review, $first->fresh()->status);
        $this->assertDatabaseCount('reservations', 1);
        $this->assertCount(6, Mail::queued(GroupMatchMail::class, fn (GroupMatchMail $mail) => $mail->kind === 'booked'));
    }

    public function test_half_hour_cutoff_does_not_create_a_reservation_for_an_incomplete_team(): void
    {
        $sport = Sport::query()->where('slug', 'padel')->firstOrFail();
        $service = app(GroupMatchService::class);
        $match = $service->create($sport, Carbon::parse('2026-11-06 18:00'), 60, $this->player('owner') + ['quoted_price' => 3500]);
        $service->confirm($match->players()->firstOrFail());
        $this->travelTo(Carbon::parse('2026-11-06 17:30'));
        $this->get(route('group-matches.index'))->assertDontSee($match->id);
        $service->reconcile();

        $this->assertSame(GroupMatch::Closed, $match->fresh()->status);
        $this->assertSame(0, Reservation::query()->count());
        $this->assertCount(1, Mail::queued(GroupMatchMail::class, fn (GroupMatchMail $mail) => $mail->kind === 'closed'));
    }

    #[DataProvider('ratingSpreads')]
    public function test_bulk_application_assigns_only_eligible_seats_automatically(int $rating, bool $requiresReview): void
    {
        $sport = Sport::query()->where('slug', 'padel')->firstOrFail();
        $service = app(GroupMatchService::class);
        $match = $service->create($sport, Carbon::parse('2026-11-06 18:00'), 60, array_replace($this->player('owner'), [
            'self_rating' => 6, 'quoted_price' => 3500, 'mixed_level' => true,
        ]));
        $service->confirm($match->players()->firstOrFail());

        $this->get(route('group-matches.show', $match))->assertOk()->assertSee('Izaberi slobodno mesto')
            ->assertSee('Dodaj i ekipu')->assertSee('type="radio"', false)
            ->assertSeeInOrder(['Ko igra', 'Ekipa A', 'owner', '6/10'])->assertDontSee('owner@example.test');
        $this->get(route('group-matches.index'))->assertOk()->assertSeeInOrder(['Prosek ocena', '6/10'])
            ->assertDontSee('Mešoviti nivo')->assertDontSee('Sličan nivo');
        $application = array_replace($this->player('captain'), ['self_rating' => $rating, 'side' => 'B', 'guests' => [
            ['name' => 'Drugi A', 'email' => 'guest-a@example.test', 'side' => 'A'],
            ['name' => 'Drugi B', 'email' => 'guest-b@example.test', 'side' => 'B'],
        ]]);
        $tooMany = $application;
        $tooMany['guests'][0]['side'] = 'B';
        $this->post(route('group-matches.join', $match), $tooMany)->assertSessionHasErrors('side');
        $this->assertEquals(1, $match->players()->count());
        $this->post(route('group-matches.join', $match), $application)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals(4, $match->players()->count());
        $this->assertEquals(1, $match->confirmedPlayers()->count());
        $this->get(route('group-matches.show', $match))->assertOk()->assertDontSee('Drugi A')->assertDontSee('Drugi B');
        $this->assertCount(4, Mail::queued(GroupMatchMail::class, fn (GroupMatchMail $mail) => $mail->kind === 'verify'));

        foreach ($match->players()->where('organizer', false)->get() as $player) {
            $questionnaire = [];
            if ($player->needsQuestionnaire()) {
                $this->get($player->signedUrl())->assertOk()->assertSee('Kako koristiš staklo u igri?');
                $this->post($player->signedUrl(), ['action' => 'confirm'])->assertSessionHasErrors('answers');
                $questionnaire = ['self_rating' => 6, 'answers' => [1, 1, 1]];
            }
            $this->post($player->signedUrl(), ['action' => 'confirm'] + $questionnaire)->assertRedirect()->assertSessionHasNoErrors();
        }
        $captain = $match->players()->where('email', 'captain@example.test')->firstOrFail();
        if ($requiresReview) {
            $this->assertNull($captain->confirmed_at);
            $this->assertSame(GroupMatchPlayer::LevelReviewPending, $captain->level_review_status);
            $this->assertSame(GroupMatch::Open, $match->fresh()->status);
            $this->assertEquals(3, $match->confirmedPlayers()->count());
            $this->get($captain->signedUrl())->assertOk()->assertSee('Arena pregleda tvoju prijavu');
            $this->get(route('group-matches.show', $match))->assertOk()
                ->assertSee('Izaberi slobodno mesto 2 u ekipi B')->assertDontSee('captain');
            $service->reconcile();
            $this->assertDatabaseCount('reservations', 0);

            $approve = $rating > 6;
            $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
            $this->actingAs($admin)->get('/admin/grupni-termini')->assertOk();
            Livewire::test(ListGroupMatches::class)
                ->assertCanSeeTableRecords([$match])
                ->callAction(TestAction::make('review_player')->table($match), data: [
                    'player' => $captain->id, 'decision' => $approve ? 'approve' : 'decline',
                ])->assertHasNoActionErrors();
            $this->assertSame($approve ? GroupMatchPlayer::LevelReviewApproved : GroupMatchPlayer::LevelReviewDeclined, $captain->fresh()->level_review_status);
            if (! $approve) {
                $this->get($captain->signedUrl())->assertOk()->assertSee('Arena nije odobrila tvoju prijavu');
                $replacement = array_replace($this->player('replacement'), ['self_rating' => 3, 'side' => 'B']);
                $service->apply($match, $replacement);
                $service->confirm($match->players()->where('email', $replacement['email'])->firstOrFail());
            }
        }
        $this->assertEquals(4, $match->confirmedPlayers()->count());
        $this->assertSame(GroupMatch::Ready, $match->fresh()->status);
        $service->reconcile();
        $this->assertSame(GroupMatch::Booked, $match->fresh()->status);
        $this->assertDatabaseCount('reservations', 1);
        $this->assertCount(4, Mail::queued(GroupMatchMail::class, fn (GroupMatchMail $mail) => $mail->kind === 'booked'));
    }

    public static function ratingSpreads(): array
    {
        return [
            '6 and 3 book automatically' => [3, false],
            '6 and 9 book automatically' => [9, false],
            '6 and 2 require review and can be declined' => [2, true],
            '6 and 10 require review and can be approved' => [10, true],
        ];
    }

    public function test_join_wizard_stores_the_answers_for_each_player_before_email_confirmation(): void
    {
        $sport = Sport::query()->where('slug', 'padel')->firstOrFail();
        $service = app(GroupMatchService::class);
        $match = $service->create($sport, Carbon::parse('2026-11-06 18:00'), 60,
            array_replace($this->player('owner'), ['self_rating' => 6, 'quoted_price' => 3500]));
        $service->confirm($match->players()->firstOrFail());

        $data = array_replace($this->player('captain'), ['self_rating' => 6, 'side' => 'B', 'group_questionnaire' => 1]);
        $data['guests']['B-2'] = ['name' => 'Pozvani igrač', 'email' => 'guest@example.test', 'side' => 'B'];
        $this->post(route('group-matches.join', $match), $data)
            ->assertSessionHasErrors(['guests.B-2.answers', 'guests.B-2.self_rating']);
        $this->assertEquals(1, $match->players()->count());

        $data['guests']['B-2'] += ['answers' => [3, 2, 1], 'self_rating' => 9];
        $this->post(route('group-matches.join', $match), $data)->assertRedirect()->assertSessionHasNoErrors();
        $guest = $match->players()->where('email', 'guest@example.test')->firstOrFail();
        $this->assertEquals(9, $guest->self_rating);
        $this->assertSame([3, 2, 1], $guest->answers);
        $this->assertEquals(6, $match->players()->where('email', 'captain@example.test')->value('self_rating'));
        $this->assertNull($guest->confirmed_at);
        $this->get($guest->signedUrl())->assertOk()->assertSee('prijavljena ocena 9/10')
            ->assertDontSee('Kako koristiš staklo u igri?');
        $this->post($guest->signedUrl(), ['action' => 'confirm'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($guest->fresh()->confirmed_at);
    }

    private function fullMatch(Sport $sport, string $prefix): GroupMatch
    {
        $service = app(GroupMatchService::class);
        $match = $service->create($sport, Carbon::parse('2026-11-06 18:00'), 60, $this->player($prefix.'-owner') + ['quoted_price' => 2500]);
        $service->confirm($match->players()->firstOrFail());
        foreach (['A', 'A', 'B', 'B', 'B'] as $index => $side) {
            $details = $this->player($prefix.'-'.$index);
            $service->apply($match, $details + ['side' => $side]);
            $service->confirm($match->players()->where('email', $details['email'])->firstOrFail());
        }

        return $match;
    }

    private function player(string $name): array
    {
        return ['name' => $name, 'email' => $name.'@example.test', 'self_rating' => 4, 'answers' => [1, 1, 1]];
    }
}
