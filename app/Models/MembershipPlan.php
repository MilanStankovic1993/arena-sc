<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MembershipPlan extends Model
{
    protected $fillable = [
        'sport_id',
        'name',
        'slug',
        'period_label',
        'duration_days',
        'reservation_limit',
        'price',
        'short_description',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'reservation_limit' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (MembershipPlan $plan): void {
            if (filled($plan->name)) {
                $baseSlug = Str::slug($plan->name);
                $slug = $baseSlug;
                $suffix = 2;

                while (static::query()
                    ->where('slug', $slug)
                    ->when($plan->exists, fn ($query) => $query->whereKeyNot($plan->getKey()))
                    ->exists()) {
                    $slug = "{$baseSlug}-{$suffix}";
                    $suffix++;
                }

                $plan->slug = $slug;
            }
        });
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }
}
