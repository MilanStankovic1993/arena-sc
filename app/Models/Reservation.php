<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'sport_id',
        'court_id',
        'status',
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
