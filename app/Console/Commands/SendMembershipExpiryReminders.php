<?php

namespace App\Console\Commands;

use App\Mail\MembershipExpiringSoonMail;
use App\Models\UserMembership;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

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
        $failed = 0;

        foreach ($memberships as $membership) {
            $daysRemaining = max(0, now()->startOfDay()->diffInDays($membership->ends_at->copy()->startOfDay(), false));

            try {
                Mail::to($membership->user->email)->send(new MembershipExpiringSoonMail($membership, $daysRemaining));
            } catch (Throwable $exception) {
                $failed++;

                Log::error('Membership expiry reminder failed.', [
                    'membership_id' => $membership->id,
                    'recipient' => $membership->user->email,
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }

            $membership->forceFill([
                'last_expiry_reminder_sent_at' => now(),
            ])->saveQuietly();

            $sent++;
        }

        $this->info("Poslato podsetnika za istek članarine: {$sent}.");

        if ($failed > 0) {
            $this->warn("Neuspesno slanje podsetnika: {$failed}.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
