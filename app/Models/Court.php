<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Court extends Model
{
    protected $appends = [
        'image_url',
    ];

    protected $fillable = [
        'sport_id',
        'name',
        'slug',
        'location',
        'surface',
        'capacity',
        'image',
        'description',
        'requires_approval',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Court $court): void {
            if (filled($court->name)) {
                $baseSlug = Str::slug($court->name);
                $slug = $baseSlug;
                $suffix = 2;

                while (static::query()
                    ->where('slug', $slug)
                    ->when($court->exists, fn ($query) => $query->whereKeyNot($court->getKey()))
                    ->exists()) {
                    $slug = "{$baseSlug}-{$suffix}";
                    $suffix++;
                }

                $court->slug = $slug;
            }
        });
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

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image ? Storage::disk('public')->url($this->image) : null);
    }
}
