<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\GroupMatch;
use App\Models\PlayerInvitation;
use App\Models\PlayerProfile;
use App\Models\Sport;
use App\Models\User;
use App\Services\GroupMatchService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlayerProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_players_use_sport_ratings_and_can_challenge_and_record_results(): void
    {
        $this->travelTo(Carbon::parse('2026-11-06 15:00:00'));
        config(['arena.seed_admin.email' => null, 'arena.seed_admin.password' => null]);
        $this->seed();
        Mail::fake();
        [$alice, $bob, $cara] = User::factory()->count(3)->create();
        $sport = Sport::query()->where('slug', 'padel')->firstOrFail();
        $basket = Sport::query()->where('slug', 'basket-3x3')->firstOrFail();
        $profileUrl = route('player-profiles.index', ['sport' => $sport->slug]);
        $this->get($profileUrl)->assertRedirect(route('login'));

        foreach ([$alice, $bob, $cara] as $user) {
            $this->actingAs($user)->post(route('player-profiles.save', ['sport' => $sport->slug]), ['starting_rating' => 6])
                ->assertRedirect($profileUrl);
        }
        $aliceProfile = PlayerProfile::query()->whereBelongsTo($alice)->sole();
        $bobProfile = PlayerProfile::query()->whereBelongsTo($bob)->sole();
        $aliceRateUrl = route('player-profiles.rate', $aliceProfile);
        $this->actingAs($alice)->post($aliceRateUrl, ['score' => 10])->assertForbidden();
        $this->actingAs($bob)->post($aliceRateUrl, ['score' => 7])->assertRedirect($profileUrl);
        $this->post($aliceRateUrl, ['score' => 9])->assertRedirect($profileUrl);
        $this->actingAs($cara)->post($aliceRateUrl, ['score' => 6])->assertRedirect($profileUrl);
        $this->assertSame(2, $aliceProfile->ratings()->count());
        $this->actingAs($alice)->get($profileUrl)->assertOk()->assertSee('7,5/10 (2)')
            ->assertSee('8/10')->assertDontSee($bob->email);
        $this->assertSame(5, PlayerProfile::matchRating($alice, $basket));

        $slot = ['sport_id' => $sport->id, 'starts_at' => '2026-11-06T18:00', 'duration_minutes' => 60];
        $this->get(route('group-matches.create', $slot))->assertOk()->assertSee('8/10')->assertDontSee('Koliko često igraš?');
        $this->post(route('group-matches.store'), $slot + ['quoted_price' => 3500, 'self_rating' => 1, 'user_id' => $bob->id])
            ->assertRedirectToRoute('group-matches.index');
        $match = GroupMatch::query()->sole();
        $organizer = $match->players()->sole();
        $this->assertSame($alice->id, $organizer->user_id);
        $this->assertSame(8, $organizer->self_rating);
        $this->get($organizer->signedUrl())->assertOk()->assertSee('Potvrdi moje mesto')->assertDontSee('Koliko često igraš?');
        $this->post($organizer->signedUrl(), ['action' => 'confirm'])->assertSessionHasNoErrors();

        $this->post(route('player-profiles.invite', $bobProfile), ['match' => $match->id, 'challenge' => 1])->assertRedirect($profileUrl);
        $invitation = PlayerInvitation::query()->sole();
        $response = route('player-profiles.respond', $invitation);
        $this->actingAs($cara)->post($response, ['accept' => 1])->assertForbidden();
        $this->actingAs($bob)->get($profileUrl)->assertOk()->assertSee('Prihvati i otvori termin');
        $joinUrl = route('group-matches.show', ['match' => $match, 'strana' => 'B']);
        $this->post($response, ['accept' => 1])->assertRedirect($joinUrl);
        $this->assertTrue($invitation->fresh()->accepted);
        $this->get($joinUrl)->assertOk()->assertSee('Ocena sa naloga za ovaj termin')->assertDontSee('data-wizard-player="self"', false);
        $this->post(route('group-matches.join', $match), ['side' => 'B'])->assertSessionHasNoErrors();
        $bobSeat = $match->players()->where('user_id', $bob->id)->sole();
        $this->assertSame(6, $bobSeat->self_rating);
        $this->post($bobSeat->signedUrl(), ['action' => 'confirm'])->assertSessionHasNoErrors();
        $this->get($profileUrl)->assertOk()->assertSee('Nema aktivnih primljenih poziva.');

        $service = app(GroupMatchService::class);
        foreach (['A', 'B'] as $side) {
            $email = 'guest-'.strtolower($side).'@example.test';
            $service->apply($match, ['name' => 'Gost '.$side, 'email' => $email, 'side' => $side, 'answers' => [1, 1, 1], 'self_rating' => 7]);
            $service->confirm($match->players()->where('email', $email)->sole());
        }
        $service->reconcile();

        $resultUrl = route('player-profiles.result', $match);
        $scores = ['own_score' => 2, 'opponent_score' => 1];
        $this->actingAs($alice)->post($resultUrl, $scores)->assertForbidden();
        $this->travelTo(Carbon::parse('2026-11-06 19:01:00'));
        $this->actingAs($cara)->post($resultUrl, $scores)->assertForbidden();
        $this->actingAs($alice)->post($resultUrl, ['own_score' => 0, 'opponent_score' => 2])->assertRedirect($profileUrl);
        $this->post($resultUrl, $scores)->assertRedirect($profileUrl);
        $this->assertSame(1, $aliceProfile->results()->count());
        $this->assertEquals(2, $aliceProfile->results()->sole()->own_score);
        $this->get($profileUrl)->assertOk()->assertSee('Setovi tvoje ekipe');
        $standing = $this->get($profileUrl)->viewData('profiles')->getCollection()->first();
        $this->assertEquals($aliceProfile->id, $standing->id);
        $this->assertEquals(1, $standing->wins);
        $match->fresh()->reservation->update(['status' => ReservationStatus::Cancelled]);
        $standing = $this->get($profileUrl)->viewData('profiles')->getCollection()->firstWhere('id', $aliceProfile->id);
        $this->assertEquals(0, $standing->wins);
    }
}
