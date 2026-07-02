<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SumateConfig extends Model
{
    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
    ];

    public static function active(): ?self
    {
        return static::where('active', true)->first();
    }
}
