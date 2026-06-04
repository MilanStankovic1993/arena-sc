<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Equipment extends Model
{
    protected $table = 'equipment';

    protected $appends = [
        'image_url',
    ];

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

    protected static function booted(): void
    {
        static::saving(function (Equipment $equipment): void {
            if (filled($equipment->name)) {
                $equipment->slug = Str::slug($equipment->name);
            }
        });
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function reservationItems(): HasMany
    {
        return $this->hasMany(ReservationEquipment::class);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image ? Storage::disk('public')->url($this->image) : null);
    }
}
