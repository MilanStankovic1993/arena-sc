<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailCampaignService
{
    public function sendToUsers(EmailCampaign $campaign, iterable $users): int
    {
        $count = 0;

        Collection::wrap($users)
            ->filter(fn ($user) => $user instanceof User)
            ->filter(fn (User $user) => filled($user->email))
            ->unique('id')
            ->each(function (User $user) use ($campaign, &$count): void {
                try {
                    Mail::to($user->email)->send(new CampaignMail($campaign, $user));
                    $count++;
                } catch (Throwable $exception) {
                    Log::error('Campaign email failed.', [
                        'campaign_id' => $campaign->id,
                        'user_id' => $user->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });

        if ($count > 0) {
            $campaign->forceFill([
                'sent_count' => $campaign->sent_count + $count,
                'last_sent_at' => now(),
            ])->save();
        }

        return $count;
    }

    public function allCustomers(): EloquentCollection
    {
        return User::query()
            ->where('role', UserRole::Customer->value)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('name')
            ->get();
    }
}
