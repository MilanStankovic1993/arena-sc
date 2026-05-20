<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    protected $fillable = [
        'court_id',
        'name',
        'day_of_week',
        'start_time',
        'end_time',
        'price',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
