<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    protected $guarded = [];

    public const STATUS_RECIBIDA = 'recibida';

    public const STATUS_EN_PROCESO = 'en_proceso';

    public const STATUS_RESUELTA = 'resuelta';

    public const STATUS_RECHAZADA = 'rechazada';

    /** @var list<string> */
    public const STATUSES = [self::STATUS_RECIBIDA, self::STATUS_EN_PROCESO, self::STATUS_RESUELTA, self::STATUS_RECHAZADA];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'recipients' => 'array',
            'handled_at' => 'datetime',
            'mailed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FormSubmissionAttachment::class);
    }

    public function scopeForForm($query, string $slug)
    {
        return $query->where('form_slug', $slug);
    }
}
