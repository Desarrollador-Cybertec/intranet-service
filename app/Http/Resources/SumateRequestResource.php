<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Forma que ve el propio reportante ("Mis reportes"): sin datos de revisión interna. */
class SumateRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'accion' => ['id' => $this->accion->slug, 'label' => $this->accion->label, 'icon' => $this->accion->icon],
            'description' => $this->description,
            'evidence' => $this->evidence,
            'status' => $this->status,
            'rejectionReason' => $this->rejection_reason,
            'pointsGranted' => $this->points_granted,
            'createdAt' => $this->created_at,
        ];
    }
}
