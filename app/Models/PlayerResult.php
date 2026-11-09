<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerResult extends Model
{
    protected $fillable = ['player_profile_id', 'group_match_id', 'own_score', 'opponent_score'];

    public function groupMatch(): BelongsTo
    {
        return $this->belongsTo(GroupMatch::class);
    }
}
