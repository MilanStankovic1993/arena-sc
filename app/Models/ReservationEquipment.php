<?php

namespace App\Models;

use App\Services\ReservationAuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ReservationEquipment extends Model
{
    public function save(array $options = [])
    {
        return DB::transaction(fn () => parent::save($options));
    }

    public function delete()
    {
        return DB::transaction(fn () => parent::delete());
    }

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
        foreach (['created' => 'equipment_added', 'updated' => 'equipment_updated', 'deleted' => 'equipment_deleted'] as $event => $label) {
            static::registerModelEvent($event, function (ReservationEquipment $item) use ($event, $label): void {
                $reservation = $item->reservation;
                if (! $reservation) {
                    return;
                }
                $new = $event === 'deleted' ? [] : $item->getAttributes();
                $old = $event === 'created' ? [] : $item->getRawOriginal();
                app(ReservationAuditService::class)->record($reservation, $label, ['equipment' => $old], ['equipment' => $new]);
            });
        }

        static::saving(function (ReservationEquipment $item): void {
            if ($item->reservation()->where('is_gift', true)->exists()) {
                $item->unit_price = 0;
                $item->line_total = 0;
            }

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
        return $this->belongsTo(Reservation::class)->withTrashed();
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
