<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class UserMembership extends Model
{
    protected $fillable = [
        'user_id',
        'membership_plan_id',
        'starts_at',
        'ends_at',
        'is_active',
        'last_expiry_reminder_sent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
            'last_expiry_reminder_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (UserMembership $membership): void {
            if (
                filled($membership->starts_at)
                && filled($membership->ends_at)
                && Carbon::parse($membership->ends_at)->lt(Carbon::parse($membership->starts_at))
            ) {
                throw ValidationException::withMessages([
                    'ends_at' => 'Datum isteka članarine ne moze biti pre datuma pocetka.',
                ]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    public function isActiveOn(CarbonInterface|string|null $date = null): bool
    {
        $date = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        return $this->is_active
            && $this->starts_at->toDateString() <= $date
            && $this->ends_at->toDateString() >= $date
            && (bool) $this->membershipPlan?->is_active;
    }
}
