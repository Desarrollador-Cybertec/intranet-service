<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $guarded = [];

    public function scopeSection($query, string $section)
    {
        return $query->where('section', $section);
    }
}
