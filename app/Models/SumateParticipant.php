<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SumateParticipant extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preconditionStatuses(): HasMany
    {
        return $this->hasMany(SumatePreconditionStatus::class, 'participant_id');
    }

    public function actionCounts(): HasMany
    {
        return $this->hasMany(SumateActionCount::class, 'participant_id');
    }
}
