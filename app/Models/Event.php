<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
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

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if (filled($event->title)) {
                $baseSlug = Str::slug($event->title);
                $slug = $baseSlug;
                $suffix = 2;

                while (static::query()
                    ->where('slug', $slug)
                    ->when($event->exists, fn ($query) => $query->whereKeyNot($event->getKey()))
                    ->exists()) {
                    $slug = "{$baseSlug}-{$suffix}";
                    $suffix++;
                }

                $event->slug = $slug;
            }
        });
    }

    public function entries(): HasMany
    {
        return $this->hasMany(EventEntry::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(EventMatch::class);
    }

    public function isLeague(): bool
    {
        return $this->type === EventType::League;
    }

    public function isTournament(): bool
    {
        return $this->type === EventType::Tournament;
    }

    protected function coverImageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null);
    }
}
