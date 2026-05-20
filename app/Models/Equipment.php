<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    protected $table = 'equipment';

    protected $fillable = [
        'sport_id',
        'name',
        'slug',
        'sku',
        'image',
        'short_description',
        'description',
        'rental_price',
        'sale_price',
        'stock_quantity',
        'is_rentable',
        'is_sellable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rental_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_rentable' => 'boolean',
            'is_sellable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function reservationItems(): HasMany
    {
        return $this->hasMany(ReservationEquipment::class);
    }
}
