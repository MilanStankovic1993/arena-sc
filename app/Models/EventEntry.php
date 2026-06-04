<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventEntry extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'team_name',
        'contact_name',
        'contact_phone',
        'played',
        'wins',
        'draws',
        'losses',
        'points',
        'score_for',
        'score_against',
        'notes',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(EventMatch::class, 'home_entry_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(EventMatch::class, 'away_entry_id');
    }
}
