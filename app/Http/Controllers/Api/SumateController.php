<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SumateActionRequest;
use App\Models\SumateAccion;
use App\Models\SumateConfig;
use App\Models\SumateNivel;
use App\Models\SumateParticipant;
use App\Models\SumatePrecondicion;
use App\Services\SumateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SumateController extends Controller
{
    public function __construct(private readonly SumateService $sumate) {}

    /**
     * GET /api/sumate/participants — objeto completo del programa (shape sumateMock).
     */
    public function participants(Request $request): JsonResponse
    {
        $config = SumateConfig::active();
        $me = $request->user()->sumateParticipant;

        $participantes = SumateParticipant::with(['actionCounts.accion', 'preconditionStatuses.precondicion'])
            ->get()
            ->map(fn (SumateParticipant $p) => $this->participantShape($p))
            ->all();

        return response()->json([
            'trimestre' => $config?->trimestre,
            'periodoLabel' => $config?->periodo_label,
            'cierreLabel' => $config?->cierre_label,
            'precondiciones' => SumatePrecondicion::orderBy('position')->orderBy('id')->get()
                ->map(fn ($p) => [
                    'id' => $p->slug,
                    'label' => $p->label,
                    'req' => $p->req,
                    'desc' => $p->desc,
                ]),
            'acciones' => SumateAccion::orderBy('position')->orderBy('id')->get()
                ->map(fn ($a) => [
                    'id' => $a->slug,
                    'label' => $a->label,
                    'icon' => $a->icon,
                    'desc' => $a->desc,
                    'ptsEach' => $a->pts_each,
                    'max' => $a->max,
                    'maxPts' => $a->max_pts,
                    'color' => $a->color,
                    'bg' => $a->bg,
                    'rango' => $a->rango,
                ]),
            'niveles' => SumateNivel::orderBy('nivel')->get()
                ->map(fn ($n) => [
                    'nivel' => $n->nivel,
                    'emoji' => $n->emoji,
                    'label' => $n->label,
                    'min' => $n->min,
                    'max' => $n->max,
                    'color' => $n->color,
                    'bg' => $n->bg,
                    'beneficio' => $n->beneficio,
                    'condicion' => $n->condicion,
                ]),
            'participantes' => $participantes,
            'myParticipantId' => $me?->id,
            'leaderboard' => $this->sumate->leaderboard($me),
        ]);
    }

    /**
     * POST /api/sumate/acciones · 👤 — registra/retira una acción del participante actual.
     */
    public function registerAction(SumateActionRequest $request): JsonResponse
    {
        $participant = $request->user()->sumateParticipant;

        if (! $participant) {
            return response()->json(['message' => 'No participas en el programa Súmate.'], 403);
        }

        $participant = $this->sumate->registerAction($participant, $request->accionId, $request->delta);

        return response()->json($this->sumate->summary($participant));
    }

    /**
     * @return array<string,mixed>
     */
    private function participantShape(SumateParticipant $p): array
    {
        $pre = [];
        foreach ($p->preconditionStatuses as $ps) {
            if ($ps->precondicion) {
                $pre[$ps->precondicion->slug] = $ps->value;
            }
        }

        $acc = [];
        foreach ($p->actionCounts as $ac) {
            if ($ac->accion) {
                $acc[$ac->accion->slug] = $ac->count;
            }
        }

        return [
            'id' => $p->id,
            'name' => $p->name,
            'initials' => $p->initials,
            'color' => $p->color,
            'area' => $p->area,
            'pre' => $pre,
            'acc' => $acc,
            'pts' => $this->sumate->pointsFor($p),
            'eligible' => $this->sumate->isEligible($p),
        ];
    }
}
