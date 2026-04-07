<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPair extends Model
{
    protected $fillable = ['match_id', 'item_a', 'item_a_image', 'item_b', 'item_b_image', 'order'];

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchGame::class, 'match_id');
    }
}
