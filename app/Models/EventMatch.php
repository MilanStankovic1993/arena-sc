<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMatch extends Model
{
    protected $fillable = [
        'event_id',
        'home_entry_id',
        'away_entry_id',
        'round_label',
        'scheduled_at',
        'status',
        'home_score',
        'away_score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function homeEntry(): BelongsTo
    {
        return $this->belongsTo(EventEntry::class, 'home_entry_id');
    }

    public function awayEntry(): BelongsTo
    {
        return $this->belongsTo(EventEntry::class, 'away_entry_id');
    }
}
