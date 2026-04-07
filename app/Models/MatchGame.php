<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchGame extends Model
{
    protected $table = 'matches';

    protected $fillable = ['title', 'mechanic', 'is_published'];

    protected $casts = ['is_published' => 'boolean'];

    public function pairs(): HasMany
    {
        return $this->hasMany(MatchPair::class, 'match_id')->orderBy('order');
    }
}
