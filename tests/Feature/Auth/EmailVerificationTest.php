<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function verificationUrl(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => $hash ?? sha1($user->getEmailForVerification()),
            ],
            absolute: false,
        );
    }

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $response = $this->actingAs($user)->get($this->verificationUrl($user));

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_can_be_verified_from_signed_link_without_current_user_session(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $response = $this->get($this->verificationUrl($user));

        Event::assertDispatched(Verified::class);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_verification_ignores_stale_intended_verify_email_page(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this
            ->withSession(['url.intended' => route('verification.notice', absolute: false)])
            ->actingAs($user)
            ->get($this->verificationUrl($user));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_verification_link_switches_to_the_link_owner_when_another_user_is_logged_in(): void
    {
        $loggedInUser = User::factory()->create();
        $linkOwner = User::factory()->unverified()->create();

        $response = $this->actingAs($loggedInUser)->get($this->verificationUrl($linkOwner));

        $this->assertAuthenticatedAs($linkOwner);
        $this->assertTrue($linkOwner->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get($this->verificationUrl($user, sha1('wrong-email')))
            ->assertRedirect(route('login'));

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_link_works_when_opened_from_a_different_host(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->get('https://scarena.rs'.$this->verificationUrl($user));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_verified_user_is_redirected_from_verification_prompt_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->withSession(['url.intended' => route('verification.notice', absolute: false)])
            ->actingAs($user)
            ->get(route('verification.notice'));

        $response->assertRedirect(route('dashboard'));
    }
}
