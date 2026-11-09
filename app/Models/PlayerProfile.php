<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerProfile extends Model
{
    public const DefaultRating = 5;

    protected $fillable = ['user_id', 'sport_id', 'starting_rating'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(PlayerRating::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(PlayerResult::class);
    }

    public static function matchRating(User $user, Sport $sport): int
    {
        $profile = self::query()->whereBelongsTo($user)->whereBelongsTo($sport)->withAvg('ratings', 'score')->first();

        return (int) round($profile?->ratings_avg_score ?? $profile?->starting_rating ?? self::DefaultRating);
    }
}
