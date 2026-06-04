<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\User;
use App\Services\EmailCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailCampaignsTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_campaign_service_sends_to_selected_unique_users(): void
    {
        Mail::fake();

        $campaign = EmailCampaign::query()->create([
            'name' => 'Jun kampanja',
            'subject' => 'Nova ponuda za clanove',
            'body' => 'Test sadrzaj kampanje.',
            'is_active' => true,
        ]);

        $first = User::factory()->create(['email' => 'prvi@example.com']);
        $second = User::factory()->create(['email' => 'drugi@example.com']);

        $sent = app(EmailCampaignService::class)->sendToUsers($campaign, [$first, $second, $first]);

        $campaign->refresh();

        $this->assertSame(2, $sent);
        $this->assertSame(2, $campaign->sent_count);
        $this->assertNotNull($campaign->last_sent_at);

        Mail::assertSent(CampaignMail::class, 2);
        Mail::assertSent(CampaignMail::class, fn (CampaignMail $mail) => $mail->hasTo('prvi@example.com'));
        Mail::assertSent(CampaignMail::class, fn (CampaignMail $mail) => $mail->hasTo('drugi@example.com'));
    }

    public function test_all_customers_returns_only_customer_users_with_email(): void
    {
        $customer = User::factory()->create([
            'role' => UserRole::Customer->value,
            'email' => 'customer@example.com',
        ]);

        User::factory()->create([
            'role' => UserRole::Customer->value,
            'email' => '',
        ]);

        User::factory()->create([
            'role' => UserRole::SuperAdmin->value,
            'email' => 'admin@example.com',
        ]);

        $users = app(EmailCampaignService::class)->allCustomers();

        $this->assertCount(1, $users);
        $this->assertTrue($users->contains(fn (User $user) => $user->is($customer)));
    }
}
