<?php

namespace App\Observers;

use App\Mail\MembershipActivatedMail;
use App\Models\UserMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class UserMembershipObserver
{
    public function saved(UserMembership $membership): void
    {
        $shouldSendActivationMail = ($membership->wasRecentlyCreated && $membership->is_active)
            || ($membership->wasChanged('is_active') && $membership->is_active);

        if (! $shouldSendActivationMail) {
            return;
        }

        $membershipId = $membership->getKey();

        DB::afterCommit(function () use ($membershipId): void {
            $membership = UserMembership::query()
                ->with(['user', 'membershipPlan.sport'])
                ->find($membershipId);

            if (! $membership || ! filled($membership->user?->email)) {
                return;
            }

            try {
                Mail::to($membership->user->email)->send(new MembershipActivatedMail($membership));
            } catch (Throwable $exception) {
                Log::error('Membership activation email failed.', [
                    'membership_id' => $membership->id,
                    'recipient' => $membership->user->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}
