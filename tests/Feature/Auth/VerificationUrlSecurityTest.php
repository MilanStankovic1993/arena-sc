<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class VerificationUrlSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_email_url_uses_configured_app_host(): void
    {
        config(['app.url' => 'https://scarena.rs']);
        $user = User::factory()->unverified()->create();

        Route::get('/_verification-url-test', function () use ($user): string {
            return (new VerifyEmail)->toMail($user)->viewData['url'];
        });

        $response = $this
            ->withServerVariables(['HTTP_HOST' => 'attacker.example'])
            ->get('http://attacker.example/_verification-url-test');

        $response
            ->assertOk()
            ->assertSee('https://scarena.rs/verify-email/', false)
            ->assertDontSee('attacker.example', false);
    }
}
