<?php

namespace App\Console\Commands;

use App\Mail\MembershipExpiringSoonMail;
use App\Models\UserMembership;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMembershipExpiryReminders extends Command
{
    protected $signature = 'memberships:send-expiry-reminders {--days= : Koliko dana pre isteka se salje podsetnik}';

    protected $description = 'Salje korisnicima email podsetnik da im članarina uskoro istice.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('arena.memberships.expiry_reminder_days', 3));
        $today = now()->toDateString();
        $targetDate = now()->addDays($days)->toDateString();

        $memberships = UserMembership::query()
            ->with(['user', 'membershipPlan.sport'])
            ->where('is_active', true)
            ->whereDate('ends_at', '>=', $today)
            ->whereDate('ends_at', '<=', $targetDate)
            ->whereNull('last_expiry_reminder_sent_at')
            ->whereHas('user', fn ($query) => $query->whereNotNull('email')->where('email', '!=', ''))
            ->whereHas('membershipPlan', fn ($query) => $query->where('is_active', true))
            ->get();

        $sent = 0;

        foreach ($memberships as $membership) {
            $daysRemaining = max(0, now()->startOfDay()->diffInDays($membership->ends_at->copy()->startOfDay(), false));

            Mail::to($membership->user->email)->send(new MembershipExpiringSoonMail($membership, $daysRemaining));

            $membership->forceFill([
                'last_expiry_reminder_sent_at' => now(),
            ])->saveQuietly();

            $sent++;
        }

        $this->info("Poslato podsetnika za istek članarine: {$sent}.");

        return self::SUCCESS;
    }
}
