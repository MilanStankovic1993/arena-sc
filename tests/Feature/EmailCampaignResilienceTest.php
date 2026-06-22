<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\EmailCampaign;
use App\Models\User;
use App\Services\EmailCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class EmailCampaignResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_continues_after_one_recipient_fails_and_counts_only_successes(): void
    {
        $campaign = EmailCampaign::query()->create([
            'name' => 'Letnja kampanja',
            'subject' => 'Novosti',
            'body' => 'Sadrzaj kampanje',
            'is_active' => true,
        ]);
        $users = User::factory()->count(2)->create([
            'role' => UserRole::Customer,
        ]);

        $attempt = 0;

        Mail::shouldReceive('to')->twice()->andReturnSelf();
        Mail::shouldReceive('send')
            ->twice()
            ->andReturnUsing(function () use (&$attempt): void {
                $attempt++;

                if ($attempt === 1) {
                    throw new RuntimeException('SMTP test failure');
                }
            });

        $sent = app(EmailCampaignService::class)->sendToUsers($campaign, $users);

        $this->assertSame(1, $sent);
        $this->assertSame(1, $campaign->fresh()->sent_count);
        $this->assertNotNull($campaign->fresh()->last_sent_at);
    }
}
