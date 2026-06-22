<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

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

    protected static function booted(): void
    {
        static::saving(function (ReservationEquipment $item): void {
            $errors = [];

            if ((int) $item->quantity < 1) {
                $errors['quantity'] = 'Kolicina opreme mora biti najmanje jedan.';
            }

            if ((float) $item->unit_price < 0) {
                $errors['unit_price'] = 'Cena opreme ne moze biti negativna.';
            }

            if ((float) $item->line_total < 0) {
                $errors['line_total'] = 'Ukupan iznos opreme ne moze biti negativan.';
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
        });
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
