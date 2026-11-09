<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerRating extends Model
{
    protected $fillable = ['player_profile_id', 'rater_id', 'score'];
}
