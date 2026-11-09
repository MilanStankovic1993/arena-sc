<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerInvitation extends Model
{
    protected $fillable = ['sender_id', 'recipient_id', 'group_match_id', 'challenge', 'accepted'];
    protected $casts = ['challenge' => 'boolean', 'accepted' => 'boolean'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function groupMatch(): BelongsTo
    {
        return $this->belongsTo(GroupMatch::class);
    }
}
