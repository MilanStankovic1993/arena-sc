<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ResetPasswordUrlSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_email_contains_complete_url_on_configured_app_host(): void
    {
        config(['app.url' => 'https://scarena.rs']);
        $user = User::factory()->create(['email' => 'korisnik@example.com']);

        Route::get('/_reset-url-test', function () use ($user): string {
            return (new ResetPassword('test-token'))->toMail($user)->actionUrl;
        });

        $response = $this
            ->withServerVariables(['HTTP_HOST' => 'attacker.example'])
            ->get('http://attacker.example/_reset-url-test');

        $response
            ->assertOk()
            ->assertSee('https://scarena.rs/reset-password/test-token', false)
            ->assertSee('email=korisnik%40example.com', false)
            ->assertDontSee('attacker.example', false);
    }
}
