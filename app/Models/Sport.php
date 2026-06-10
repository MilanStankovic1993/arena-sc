<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Sport extends Model
{
    protected $appends = [
        'cover_image_url',
    ];

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'cover_image',
        'supports_online_booking',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'supports_online_booking' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Sport $sport): void {
            if (filled($sport->name)) {
                $sport->slug = Str::slug($sport->name);
            }
        });
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    public function membershipPlans(): HasMany
    {
        return $this->hasMany(MembershipPlan::class);
    }

    protected function coverImageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null);
    }
}
