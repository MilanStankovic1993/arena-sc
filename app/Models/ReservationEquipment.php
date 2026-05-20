<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationEquipment extends Model
{
    protected $table = 'reservation_equipment';

    protected $fillable = [
        'reservation_id',
        'equipment_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
