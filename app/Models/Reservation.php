<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Reservation extends Model
{
    use SoftDeletes {
        restore as protected restoreSoftDeleted;
    }

    public function save(array $options = [])
    {
        return DB::transaction(fn () => parent::save($options));
    }

    public function delete()
    {
        return DB::transaction(function () {
            $current = static::withTrashed()->whereKey($this->getKey())->lockForUpdate()->first();
            if (! $current || $current->trashed()) {
                return false;
            }
            $this->setRawAttributes($current->getAttributes(), true);
            $this->unsetRelations();

            return parent::delete();
        });
    }

    public function restore()
    {
        return DB::transaction(function () {
            $current = static::withTrashed()->whereKey($this->getKey())->lockForUpdate()->first();
            if (! $current?->trashed()) {
                return false;
            }
            $this->setRawAttributes($current->getAttributes(), true);
            $this->unsetRelations();
            $this->restoringReservation = true;
            try {
                return $this->restoreSoftDeleted();
            } finally {
                $this->restoringReservation = false;
            }
        });
    }

    public function forceDelete()
    {
        throw ValidationException::withMessages(['reservation' => 'Trajno brisanje rezervacija nije dozvoljeno. Koristite prikaz obrisanih termina.']);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ReservationAudit::class)->latest('id');
    }
    public bool $suppressNotification = false;
    public bool $notifyReservationCreated = false;
    public bool $restoringReservation = false;

    protected $fillable = [
        'user_id',
        'series_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'sport_id',
        'court_id',
        'status',
        'is_gift',
        'starts_at',
        'ends_at',
        'duration_minutes',
        'players_count',
        'court_price',
        'equipment_price',
        'total_price',
        'customer_note',
        'admin_note',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'is_gift' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'court_price' => 'decimal:2',
            'equipment_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayStatusAttribute(): string
    {
        return ($this->trashed() ? 'Obrisana · ' : '').$this->status->label().($this->is_gift ? ' · Poklon' : '');
    }

    public function getCustomerDisplayNameAttribute(): string
    {
        return $this->user?->name ?: ($this->guest_name ?: 'Gost');
    }

    public function getCustomerDisplayEmailAttribute(): ?string
    {
        return $this->user?->email ?: $this->guest_email;
    }

    public function getCustomerDisplayPhoneAttribute(): ?string
    {
        return $this->user?->phone ?: $this->guest_phone;
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function equipmentItems(): HasMany
    {
        return $this->hasMany(ReservationEquipment::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reservation_participants')->withTimestamps();
    }
}
