<?php

namespace App\Observers;

use App\Mail\MembershipActivatedMail;
use App\Models\UserMembership;
use Illuminate\Support\Facades\Mail;

class UserMembershipObserver
{
    public function saved(UserMembership $membership): void
    {
        $membership->loadMissing('user');

        if (! filled($membership->user?->email)) {
            return;
        }

        $shouldSendActivationMail = ($membership->wasRecentlyCreated && $membership->is_active)
            || ($membership->wasChanged('is_active') && $membership->is_active);

        if (! $shouldSendActivationMail) {
            return;
        }

        Mail::to($membership->user->email)->send(new MembershipActivatedMail($membership));
    }
}
