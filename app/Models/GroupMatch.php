<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupMatch extends Model
{
    use HasUuids;

    public const Draft = 'draft';
    public const Open = 'open';
    public const Ready = 'ready';
    public const Review = 'review';
    public const Booked = 'booked';
    public const Closed = 'closed';
    public const Pending = [self::Draft, self::Open, self::Ready, self::Review];
    public const AutomaticRatingSpread = 3;

    protected $fillable = [
        'sport_id', 'reservation_id', 'status', 'starts_at', 'ends_at', 'closes_at',
        'duration_minutes', 'quoted_price', 'team_size', 'mixed_level', 'team_invite', 'closing_note',
    ];

    protected $hidden = ['team_invite'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'closes_at' => 'datetime',
        'quoted_price' => 'decimal:2',
        'mixed_level' => 'boolean',
    ];

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class)->withTrashed();
    }

    public function players(): HasMany
    {
        return $this->hasMany(GroupMatchPlayer::class);
    }

    public function confirmedPlayers(): HasMany
    {
        return $this->players()->whereNotNull('confirmed_at');
    }

    public function playersAwaitingLevelReview(): HasMany
    {
        return $this->players()->where('level_review_status', GroupMatchPlayer::LevelReviewPending);
    }

    public function capacity(): int
    {
        return 2 * $this->team_size;
    }

    public function hasActiveReservation(): bool
    {
        return $this->reservation?->status === ReservationStatus::Reserved && ! $this->reservation->trashed();
    }

    public function scopePlayed(Builder $query): Builder
    {
        return $query->where('status', self::Booked)->whereHas('reservation', fn (Builder $reservation) => $reservation
            ->whereNull('deleted_at')->where('status', ReservationStatus::Reserved)->where('ends_at', '<=', now()));
    }

    public function joiningSides(?string $invite): array
    {
        if (! $this->team_invite) {
            return ['A', 'B'];
        }

        return hash_equals($this->team_invite, $invite ?? '') ? ['A'] : ['B'];
    }

    public function needsLevelReview(?int $candidateRating = null): bool
    {
        // An approved exception has already been reviewed and cannot block later applications.
        $ratings = $this->confirmedPlayers->whereNull('level_review_status')->pluck('self_rating');
        if ($candidateRating !== null) {
            $ratings->push($candidateRating);
        }

        return $ratings->isNotEmpty() && $ratings->max() - $ratings->min() > self::AutomaticRatingSpread;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::Booked && $this->reservation?->display_status) {
            return $this->reservation->display_status;
        }

        return match ($this->status) {
            self::Draft => 'Čeka potvrdu mejla',
            self::Open => 'Traži igrače',
            self::Ready => 'Ekipa je popunjena',
            self::Review => 'Na pregledu Arene',
            self::Booked => 'Rezervisano',
            self::Closed => 'Zatvoren',
        };
    }
}
