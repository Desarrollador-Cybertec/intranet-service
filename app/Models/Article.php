<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $guarded = [];

    protected $casts = [
        'imgs' => 'array',
        'event_date' => 'date',
    ];

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
