<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Forma que ve el dueño de la solicitud ("Mis solicitudes"): sin datos de gestión interna. */
class FormSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'formSlug' => $this->form_slug,
            'payload' => $this->payload,
            'status' => $this->status,
            'notes' => $this->notes,
            'createdAt' => $this->created_at,
            'attachments' => FormSubmissionAttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
