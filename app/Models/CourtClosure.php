<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class CourtClosure extends Model
{
    protected $fillable = [
        'court_id',
        'title',
        'reason',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CourtClosure $closure): void {
            if ($closure->starts_at && $closure->ends_at && $closure->starts_at->gte($closure->ends_at)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'Kraj blokade mora biti posle pocetka.',
                ]);
            }
        });
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
