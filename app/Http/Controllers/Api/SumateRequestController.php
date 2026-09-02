<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectSumateRequestRequest;
use App\Http\Requests\StoreSumateRequestRequest;
use App\Http\Resources\SumateRequestAdminResource;
use App\Http\Resources\SumateRequestResource;
use App\Models\SumateParticipant;
use App\Models\SumateRequest;
use App\Services\SumateRequestService;
use App\Services\SumateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SumateRequestController extends Controller
{
    public function __construct(
        private readonly SumateRequestService $requests,
        private readonly SumateService $sumate,
    ) {}

    /** POST /api/sumate/solicitudes — el colaborador reporta una acción propia. */
    public function store(StoreSumateRequestRequest $request): JsonResponse
    {
        $participant = $request->user()->sumateParticipant;
        abort_if(! $participant, 422, 'No tienes un participante de Súmate asociado.');

        $sumateRequest = $this->requests->create(
            $participant,
            $request->user(),
            $request->string('accionId')->toString(),
            $request->string('description')->toString(),
            $request->filled('evidence') ? $request->string('evidence')->toString() : null,
        );

        return (new SumateRequestResource($sumateRequest->load('accion')))->response()->setStatusCode(201);
    }

    /** GET /api/sumate/solicitudes/mias — mis propios reportes, cualquier estado. */
    public function mine(Request $request): JsonResponse
    {
        $participant = $request->user()->sumateParticipant;
        $items = $participant
            ? SumateRequest::with('accion')->where('participant_id', $participant->id)->latest()->get()
            : collect();

        return $this->items(SumateRequestResource::collection($items));
    }

    /** GET /api/sumate/solicitudes?status= — bandeja de aprobación. */
    public function index(Request $request): JsonResponse
    {
        $query = SumateRequest::with(['participant', 'user', 'accion', 'reviewedBy']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $this->items(SumateRequestAdminResource::collection($query->latest()->get()));
    }

    /** POST /api/sumate/solicitudes/{sumateRequest}/aprobar */
    public function approve(Request $request, SumateRequest $sumateRequest): JsonResponse
    {
        $updated = $this->requests->approve($sumateRequest, $request->user());
        $participant = SumateParticipant::findOrFail($updated->participant_id);

        return response()->json([
            'request' => new SumateRequestAdminResource($updated->load(['participant', 'user', 'accion', 'reviewedBy'])),
            'participant' => $this->sumate->summary($participant),
        ]);
    }

    /** POST /api/sumate/solicitudes/{sumateRequest}/rechazar */
    public function reject(RejectSumateRequestRequest $request, SumateRequest $sumateRequest): SumateRequestAdminResource
    {
        $updated = $this->requests->reject($sumateRequest, $request->user(), $request->string('reason')->toString());

        return new SumateRequestAdminResource($updated->load(['participant', 'user', 'accion', 'reviewedBy']));
    }
}
