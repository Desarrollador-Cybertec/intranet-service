<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SumateActionCount extends Model
{
    protected $guarded = [];

    public function accion(): BelongsTo
    {
        return $this->belongsTo(SumateAccion::class, 'accion_id');
    }
}
