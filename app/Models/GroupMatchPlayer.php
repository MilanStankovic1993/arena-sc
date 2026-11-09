<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class GroupMatchPlayer extends Model
{
    use HasUuids;

    public const LevelReviewPending = 'pending';
    public const LevelReviewApproved = 'approved';
    public const LevelReviewDeclined = 'declined';

    protected $fillable = ['user_id', 'name', 'email', 'side', 'organizer', 'self_rating', 'level', 'answers', 'confirmed_at', 'level_review_status'];
    protected $hidden = ['email', 'answers'];

    protected $casts = [
        'answers' => 'array',
        'organizer' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function groupMatch(): BelongsTo
    {
        return $this->belongsTo(GroupMatch::class);
    }

    public function signedUrl(): string
    {
        return URL::temporarySignedRoute('group-matches.player', $this->groupMatch->closes_at, ['player' => $this]);
    }

    public function needsQuestionnaire(): bool
    {
        return (int) $this->self_rating === 0;
    }
}
