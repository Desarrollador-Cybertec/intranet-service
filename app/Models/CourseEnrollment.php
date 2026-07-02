<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'completed' => 'boolean',
    ];
}
