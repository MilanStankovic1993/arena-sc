<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

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

    protected static function booted(): void
    {
        static::saving(function (EventMatch $match): void {
            $errors = [];

            if ($match->home_entry_id
                && $match->away_entry_id
                && (int) $match->home_entry_id === (int) $match->away_entry_id) {
                $errors['away_entry_id'] = 'Domacin i gost ne mogu biti isti tim.';
            }

            $entries = EventEntry::query()
                ->whereIn('id', array_filter([$match->home_entry_id, $match->away_entry_id]))
                ->get()
                ->keyBy('id');

            foreach (['home_entry_id', 'away_entry_id'] as $entryField) {
                $entryId = $match->{$entryField};

                if ($entryId && (int) $entries->get($entryId)?->event_id !== (int) $match->event_id) {
                    $errors[$entryField] = 'Izabrani tim ne pripada ovom dogadjaju.';
                }
            }

            if ($match->status === 'finished') {
                if (! $match->home_entry_id || ! $match->away_entry_id) {
                    $errors['home_entry_id'] = 'Zavrsen mec mora imati domacina i gosta.';
                }

                if ($match->home_score === null || $match->away_score === null) {
                    $errors['home_score'] = 'Zavrsen mec mora imati oba rezultata.';
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
        });
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
