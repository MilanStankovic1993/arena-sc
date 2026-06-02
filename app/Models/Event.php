<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    protected $appends = [
        'cover_image_url',
    ];

    protected $fillable = [
        'title',
        'slug',
        'type',
        'status',
        'location',
        'cover_image',
        'cta_label',
        'summary',
        'description',
        'rules',
        'start_date',
        'end_date',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'status' => EventStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'is_featured' => 'boolean',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(EventEntry::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(EventMatch::class);
    }

    protected function coverImageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null);
    }
}
