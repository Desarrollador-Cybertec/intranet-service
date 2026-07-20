<?php

namespace App\Services;

use App\Exceptions\NotEligibleException;
use App\Models\SumateAccion;
use App\Models\SumateNivel;
use App\Models\SumateParticipant;
use App\Models\SumatePrecondicion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Lógica del programa Súmate. TODA se recalcula server-side (el cliente solo la refleja).
 * Referencia: .context/mocks/.context/03-flujos.md § Súmate.
 */
class SumateService
{
    public const MAX_TOTAL = 100;

    /**
     * Puntos totales de un participante.
     * Por acción: min(count, max) * pts_each, tope max_pts. Total = suma, cap 100.
     */
    public function pointsFor(SumateParticipant $participant): int
    {
        $participant->loadMissing('actionCounts.accion');

        $total = 0;
        foreach ($participant->actionCounts as $ac) {
            $accion = $ac->accion;
            if (! $accion) {
                continue;
            }
            $raw = min($ac->count, $accion->max) * $accion->pts_each;
            $total += min($raw, $accion->max_pts);
        }

        return min($total, self::MAX_TOTAL);
    }

    /**
     * Elegible solo si las 5 precondiciones son true.
     */
    public function isEligible(SumateParticipant $participant): bool
    {
        $participant->loadMissing('preconditionStatuses');

        if ($participant->preconditionStatuses->isEmpty()) {
            return false;
        }

        return $participant->preconditionStatuses->every(fn ($s) => $s->value === true);
    }

    /**
     * Nivel alcanzado según puntos, solo si es elegible. null si no aplica.
     */
    public function levelFor(SumateParticipant $participant): ?SumateNivel
    {
        if (! $this->isEligible($participant)) {
            return null;
        }

        $points = $this->pointsFor($participant);

        return SumateNivel::where('min', '<=', $points)
            ->where('max', '>=', $points)
            ->first();
    }

    /**
     * Crea o actualiza el participante ligado a un usuario, copiando su perfil.
     * Idempotente: se puede llamar en cada import, registro o edición de perfil.
     */
    public function syncParticipantFor(User $user): SumateParticipant
    {
        $participant = SumateParticipant::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $user->name,
                'initials' => $user->initials ?: User::initialsFrom($user->name),
                'color' => $user->color ?: User::colorFrom($user->email),
                'area' => $user->area ?? '',
            ],
        );

        // Todo participante arranca con las precondiciones en false; el admin las valida.
        foreach (SumatePrecondicion::pluck('id') as $precondicionId) {
            $participant->preconditionStatuses()->firstOrCreate(
                ['precondicion_id' => $precondicionId],
                ['value' => false],
            );
        }

        return $participant;
    }

    /**
     * Marca las precondiciones de un participante. Solo aplica los slugs recibidos.
     *
     * @param  array<string,bool>  $pre
     */
    public function setPreconditions(SumateParticipant $participant, array $pre): SumateParticipant
    {
        $ids = SumatePrecondicion::pluck('id', 'slug');

        DB::transaction(function () use ($participant, $pre, $ids) {
            foreach ($pre as $slug => $value) {
                if (! isset($ids[$slug])) {
                    continue;
                }

                $participant->preconditionStatuses()->updateOrCreate(
                    ['precondicion_id' => $ids[$slug]],
                    ['value' => (bool) $value],
                );
            }
        });

        return $participant->fresh(['actionCounts.accion', 'preconditionStatuses.precondicion']);
    }

    /**
     * Registra/retira una acción respetando el max por acción. Recalcula estado.
     * $delta puede ser positivo o negativo. El count nunca baja de 0 ni supera max.
     *
     * @throws NotEligibleException si se intenta sumar a un participante no elegible.
     */
    public function registerAction(SumateParticipant $participant, string $accionSlug, int $delta): SumateParticipant
    {
        if ($delta > 0 && ! $this->isEligible($participant)) {
            throw new NotEligibleException(
                'El participante no cumple todas las pre-condiciones y no puede acumular puntos.'
            );
        }

        $accion = SumateAccion::where('slug', $accionSlug)->firstOrFail();

        DB::transaction(function () use ($participant, $accion, $delta) {
            $count = $participant->actionCounts()
                ->where('accion_id', $accion->id)
                ->first();

            $current = $count?->count ?? 0;
            $next = max(0, min($accion->max, $current + $delta));

            $participant->actionCounts()->updateOrCreate(
                ['accion_id' => $accion->id],
                ['count' => $next],
            );
        });

        return $participant->fresh(['actionCounts.accion', 'preconditionStatuses']);
    }

    /**
     * Resumen del participante para la respuesta (puntos, elegibilidad, nivel, conteos).
     *
     * @return array<string,mixed>
     */
    public function summary(SumateParticipant $participant): array
    {
        $participant->loadMissing(['actionCounts.accion', 'preconditionStatuses.precondicion']);

        $acc = [];
        foreach ($participant->actionCounts as $ac) {
            if ($ac->accion) {
                $acc[$ac->accion->slug] = $ac->count;
            }
        }

        $pre = [];
        foreach ($participant->preconditionStatuses as $ps) {
            if ($ps->precondicion) {
                $pre[$ps->precondicion->slug] = $ps->value;
            }
        }

        $level = $this->levelFor($participant);

        return [
            'id' => $participant->id,
            'name' => $participant->name,
            'initials' => $participant->initials,
            'color' => $participant->color,
            'area' => $participant->area,
            'pre' => $pre,
            'acc' => $acc,
            'pts' => $this->pointsFor($participant),
            'eligible' => $this->isEligible($participant),
            'nivel' => $level?->nivel,
        ];
    }

    /**
     * Leaderboard ordenado por puntos, con posición del participante actual.
     *
     * @return array<string,mixed>
     */
    public function leaderboard(?SumateParticipant $me = null): array
    {
        $participants = SumateParticipant::with(['actionCounts.accion', 'preconditionStatuses'])->get();

        $players = $participants
            ->map(fn (SumateParticipant $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'initials' => $p->initials,
                'color' => $p->color,
                'area' => $p->area,
                'pts' => $this->pointsFor($p),
            ])
            ->sortByDesc('pts')
            ->values();

        $myPoints = 0;
        $myRank = null;
        if ($me) {
            $myPoints = $this->pointsFor($me);
            $myRank = $players->search(fn ($pl) => $pl['id'] === $me->id);
            $myRank = $myRank === false ? null : $myRank + 1;
        }

        return [
            'players' => $players->all(),
            'myPoints' => $myPoints,
            'myRank' => $myRank,
        ];
    }
}
