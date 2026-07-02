<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SumatePreconditionStatus extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'boolean',
    ];

    public function precondicion(): BelongsTo
    {
        return $this->belongsTo(SumatePrecondicion::class, 'precondicion_id');
    }
}
