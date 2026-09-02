<?php

namespace App\Http\Resources;

use App\Services\SumateService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Forma que ve quien aprueba (bandeja de solicitudes Súmate). */
class SumateRequestAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sumate = app(SumateService::class);
        $participant = $this->participant;

        return [
            'id' => $this->id,
            'accion' => ['id' => $this->accion->slug, 'label' => $this->accion->label, 'icon' => $this->accion->icon, 'ptsEach' => $this->accion->pts_each, 'max' => $this->accion->max],
            'participant' => ['id' => $participant->id, 'name' => $participant->name],
            'user' => ['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email],
            'description' => $this->description,
            'evidence' => $this->evidence,
            'formSubmissionId' => $this->form_submission_id,
            'status' => $this->status,
            'reviewedBy' => $this->whenLoaded('reviewedBy', fn () => $this->reviewedBy ? ['id' => $this->reviewedBy->id, 'name' => $this->reviewedBy->name] : null),
            'reviewedAt' => $this->reviewed_at,
            'rejectionReason' => $this->rejection_reason,
            'granted' => $this->granted,
            'grantedAt' => $this->granted_at,
            'pointsGranted' => $this->points_granted,
            'createdAt' => $this->created_at,
            // Para que quien aprueba vea, antes de decidir, si esto realmente va a sumar puntos.
            'participantEligible' => $sumate->isEligible($participant),
            'participantRemaining' => max(0, SumateService::MAX_TOTAL - $sumate->pointsFor($participant)),
        ];
    }
}
