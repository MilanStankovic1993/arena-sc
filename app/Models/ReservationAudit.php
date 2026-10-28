<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationAudit extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class)->withTrashed();
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'created' => 'Kreirana', 'updated' => 'Izmenjena', 'deleted' => 'Obrisana',
            'restored' => 'Vraćena', 'participants' => 'Izmenjeni učesnici',
            'equipment_added' => 'Dodata oprema', 'equipment_updated' => 'Izmenjena oprema',
            'equipment_deleted' => 'Uklonjena oprema', default => $this->event,
        };
    }
}
