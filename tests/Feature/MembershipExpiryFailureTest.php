<?php

namespace Tests\Feature;

use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class MembershipExpiryFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_command_reports_failure_and_leaves_reminder_retryable(): void
    {
        Mail::fake();

        $plan = MembershipPlan::query()->create([
            'name' => 'Mesecna clanarina',
            'duration_days' => 30,
            'reservation_limit' => 3,
            'price' => 12000,
            'is_active' => true,
        ]);
        $membership = UserMembership::query()->create([
            'user_id' => User::factory()->create()->id,
            'membership_plan_id' => $plan->id,
            'starts_at' => now()->subDays(27)->toDateString(),
            'ends_at' => now()->addDays(3)->toDateString(),
            'is_active' => true,
        ]);

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP test failure'));

        $this->artisan('memberships:send-expiry-reminders')
            ->expectsOutput('Neuspesno slanje podsetnika: 1.')
            ->assertExitCode(1);

        $this->assertNull($membership->fresh()->last_expiry_reminder_sent_at);
    }
}
