<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nombrado SumateRequest (no SumateActionRequest) porque ya existe un FormRequest
 * con ese nombre para el otorgamiento directo de acciones (POST /api/sumate/acciones).
 */
class SumateRequest extends Model
{
    protected $table = 'sumate_action_requests';

    protected $guarded = [];

    public const STATUS_PENDIENTE = 'pendiente';

    public const STATUS_APROBADA = 'aprobada';

    public const STATUS_RECHAZADA = 'rechazada';

    /** @var list<string> */
    public const STATUSES = [self::STATUS_PENDIENTE, self::STATUS_APROBADA, self::STATUS_RECHAZADA];

    protected function casts(): array
    {
        return [
            'granted' => 'boolean',
            'reviewed_at' => 'datetime',
            'granted_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(SumateParticipant::class, 'participant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accion(): BelongsTo
    {
        return $this->belongsTo(SumateAccion::class, 'accion_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function formSubmission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class);
    }
}
