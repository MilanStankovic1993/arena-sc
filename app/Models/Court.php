<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    protected $fillable = [
        'sport_id',
        'name',
        'slug',
        'location',
        'surface',
        'capacity',
        'image',
        'description',
        'base_price',
        'requires_approval',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function closures(): HasMany
    {
        return $this->hasMany(CourtClosure::class);
    }
}
