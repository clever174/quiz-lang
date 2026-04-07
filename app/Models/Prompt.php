<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prompt extends Model
{
    protected $fillable = ['key', 'label', 'text'];

    public static function get(string $key): ?string
    {
        return static::where('key', $key)->value('text');
    }
}
