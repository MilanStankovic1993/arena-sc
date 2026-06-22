<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    protected $fillable = [
        'name',
        'subject',
        'preheader',
        'heading',
        'hero_image',
        'intro',
        'body',
        'cta_label',
        'cta_url',
        'footer_note',
        'is_active',
        'sent_count',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        if (! $this->hero_image) {
            return null;
        }

        return route('public-storage.show', ['path' => ltrim($this->hero_image, '/')]);
    }
}
