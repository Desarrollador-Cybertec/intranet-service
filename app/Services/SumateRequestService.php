<?php

namespace App\Services;

use App\Exceptions\NotEligibleException;
use App\Models\SumateAccion;
use App\Models\SumateParticipant;
use App\Models\SumateRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Autogestión de Súmate: el colaborador reporta una acción, SIG/SST la aprueba y
 * los puntos se suman al aprobar (no al reportar). Toda la matemática de puntos se
 * delega en SumateService::registerAction() — este servicio solo orquesta el estado
 * de la solicitud (pendiente/aprobada/rechazada) y el antispam.
 */
class SumateRequestService
{
    public function __construct(private readonly SumateService $sumate) {}

    public function create(SumateParticipant $participant, User $reporter, string $accionSlug, string $description, ?string $evidence, ?int $formSubmissionId = null): SumateRequest
    {
        $accion = SumateAccion::where('slug', $accionSlug)->firstOrFail();

        $yaPendiente = SumateRequest::where('participant_id', $participant->id)
            ->where('accion_id', $accion->id)
            ->where('status', SumateRequest::STATUS_PENDIENTE)
            ->exists();

        if ($yaPendiente) {
            throw new HttpException(422, 'Ya tienes una solicitud pendiente para esta acción.');
        }

        return SumateRequest::create([
            'participant_id' => $participant->id,
            'user_id' => $reporter->id,
            'accion_id' => $accion->id,
            'description' => $description,
            'evidence' => $evidence,
            'form_submission_id' => $formSubmissionId,
            'status' => SumateRequest::STATUS_PENDIENTE,
        ]);
    }

    /**
     * @throws NotEligibleException si el participante no cumple las pre-condiciones
     *                              (la solicitud queda pendiente, no se marca rechazada
     *                              automáticamente: es un estado transitorio a corregir).
     * @throws HttpException 409 si la solicitud ya no está pendiente (doble aprobación).
     */
    public function approve(SumateRequest $request, User $reviewer): SumateRequest
    {
        return DB::transaction(function () use ($request, $reviewer) {
            /** @var SumateRequest $locked */
            $locked = SumateRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== SumateRequest::STATUS_PENDIENTE) {
                throw new HttpException(409, 'Esta solicitud ya fue revisada.');
            }

            $participant = SumateParticipant::findOrFail($locked->participant_id);
            $accion = SumateAccion::findOrFail($locked->accion_id);

            $before = $this->sumate->pointsFor($participant);
            // NotEligibleException se deja propagar tal cual (ya se mapea a 422 en
            // el otorgamiento directo); la solicitud permanece pendiente para reintentar
            // una vez el participante cumpla las pre-condiciones.
            $participant = $this->sumate->registerAction($participant, $accion->slug, 1);
            $after = $this->sumate->pointsFor($participant);

            $locked->update([
                'status' => SumateRequest::STATUS_APROBADA,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'granted' => true,
                'granted_at' => now(),
                'points_granted' => $after - $before,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * @throws HttpException 409 si la solicitud ya no está pendiente.
     */
    public function reject(SumateRequest $request, User $reviewer, string $reason): SumateRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $reason) {
            $locked = SumateRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== SumateRequest::STATUS_PENDIENTE) {
                throw new HttpException(409, 'Esta solicitud ya fue revisada.');
            }

            $locked->update([
                'status' => SumateRequest::STATUS_RECHAZADA,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $locked->fresh();
        });
    }
}
