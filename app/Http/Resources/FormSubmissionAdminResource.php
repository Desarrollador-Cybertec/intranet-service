<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Forma que ve quien gestiona el área dueña del formulario (bandeja de solicitudes). */
class FormSubmissionAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'formSlug' => $this->form_slug,
            'payload' => $this->payload,
            'status' => $this->status,
            'notes' => $this->notes,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'handledBy' => $this->whenLoaded('handledBy', fn () => $this->handledBy ? [
                'id' => $this->handledBy->id,
                'name' => $this->handledBy->name,
            ] : null),
            'handledAt' => $this->handled_at,
            'mailedAt' => $this->mailed_at,
            'createdAt' => $this->created_at,
            'attachments' => FormSubmissionAttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
