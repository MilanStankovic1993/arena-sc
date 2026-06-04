<?php

namespace App\Services;

use App\Mail\CampaignMail;
use App\Models\EmailCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

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
                Mail::to($user->email)->send(new CampaignMail($campaign, $user));
                $count++;
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
            ->where('role', \App\Enums\UserRole::Customer->value)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('name')
            ->get();
    }
}
