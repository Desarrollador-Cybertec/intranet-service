<?php

namespace App\Services;

use App\Exceptions\AutoPreconditionException;
use App\Exceptions\NotEligibleException;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\SumateAccion;
use App\Models\SumateNivel;
use App\Models\SumateParticipant;
use App\Models\SumatePrecondicion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lógica del programa Súmate. TODA se recalcula server-side (el cliente solo la refleja).
 * Referencia: .context/mocks/.context/03-flujos.md § Súmate.
 */
class SumateService
{
    public const MAX_TOTAL = 100;

    /** @var Collection<int,SumatePrecondicion>|null */
    private $precondiciones = null;

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
     * Precarga en 3 consultas lo que necesitan las pre-condiciones automáticas,
     * para no hacer N+1 al resolver los ~125 participantes de un trimestre.
     *
     * @return array{joinedAt: array<int,mixed>, obligatorios: int, completados: array<int,int>}
     */
    public function autoContext(): array
    {
        $obligatorioIds = Course::where('tag', 'Obligatorio')->pluck('id');

        $completados = $obligatorioIds->isEmpty()
            ? collect()
            : CourseEnrollment::where('completed', true)
                ->whereIn('course_id', $obligatorioIds)
                ->selectRaw('user_id, COUNT(*) as total')
                ->groupBy('user_id')
                ->pluck('total', 'user_id');

        return [
            'joinedAt' => User::whereNotNull('joined_at')->pluck('joined_at', 'id')->all(),
            'obligatorios' => $obligatorioIds->count(),
            'completados' => $completados->all(),
        ];
    }

    /**
     * Valor derivado de una pre-condición automática, con el detalle que la UI
     * muestra como tooltip ("1a 4m", "3/5 cursos").
     *
     * @param  array{joinedAt: array<int,mixed>, obligatorios: int, completados: array<int,int>}  $ctx
     * @return array{value: bool, detail: string}
     */
    public function autoValueFor(SumateParticipant $participant, string $source, array $ctx): array
    {
        $userId = $participant->user_id;

        return match ($source) {
            'antiguedad' => $this->antiguedadFor($userId, $ctx),
            'capacitaciones' => $this->capacitacionesFor($userId, $ctx),
            default => ['value' => false, 'detail' => 'Sin fuente'],
        };
    }

    /**
     * Estado real de las 5 pre-condiciones: las automáticas se calculan aquí y
     * las manuales se leen de sumate_precondition_statuses.
     *
     * @param  array{joinedAt: array<int,mixed>, obligatorios: int, completados: array<int,int>}|null  $ctx
     * @return array{pre: array<string,bool>, autoDetail: array<string,string>}
     */
    public function preconditionsFor(SumateParticipant $participant, ?array $ctx = null): array
    {
        $ctx ??= $this->autoContext();
        $participant->loadMissing('preconditionStatuses.precondicion');

        $stored = [];
        foreach ($participant->preconditionStatuses as $ps) {
            if ($ps->precondicion) {
                $stored[$ps->precondicion->slug] = (bool) $ps->value;
            }
        }

        $pre = [];
        $autoDetail = [];

        foreach ($this->precondiciones() as $precondicion) {
            if ($precondicion->auto_source) {
                $auto = $this->autoValueFor($participant, $precondicion->auto_source, $ctx);
                $pre[$precondicion->slug] = $auto['value'];
                $autoDetail[$precondicion->slug] = $auto['detail'];

                continue;
            }

            $pre[$precondicion->slug] = $stored[$precondicion->slug] ?? false;
        }

        return ['pre' => $pre, 'autoDetail' => $autoDetail];
    }

    /**
     * Elegible solo si las 5 precondiciones (automáticas + manuales) son true.
     *
     * @param  array{joinedAt: array<int,mixed>, obligatorios: int, completados: array<int,int>}|null  $ctx
     */
    public function isEligible(SumateParticipant $participant, ?array $ctx = null): bool
    {
        $pre = $this->preconditionsFor($participant, $ctx)['pre'];

        if ($pre === []) {
            return false;
        }

        return ! in_array(false, $pre, true);
    }

    /**
     * Nivel alcanzado según puntos, solo si es elegible. null si no aplica.
     *
     * @param  array{joinedAt: array<int,mixed>, obligatorios: int, completados: array<int,int>}|null  $ctx
     */
    public function levelFor(SumateParticipant $participant, ?array $ctx = null): ?SumateNivel
    {
        if (! $this->isEligible($participant, $ctx)) {
            return null;
        }

        $points = $this->pointsFor($participant);

        return SumateNivel::where('min', '<=', $points)
            ->where('max', '>=', $points)
            ->first();
    }

    /**
     * Catálogo de pre-condiciones, cacheado por request.
     *
     * @return Collection<int,SumatePrecondicion>
     */
    public function precondiciones()
    {
        return $this->precondiciones ??= SumatePrecondicion::orderBy('position')->orderBy('id')->get();
    }

    /**
     * Antigüedad: más de 3 meses desde la fecha de ingreso del usuario.
     *
     * @param  array{joinedAt: array<int,mixed>, obligatorios: int, completados: array<int,int>}  $ctx
     * @return array{value: bool, detail: string}
     */
    private function antiguedadFor(?int $userId, array $ctx): array
    {
        $joinedAt = $userId === null ? null : ($ctx['joinedAt'][$userId] ?? null);

        if ($joinedAt === null) {
            return ['value' => false, 'detail' => 'Sin fecha de ingreso'];
        }

        $joined = Carbon::parse($joinedAt);
        $months = (int) $joined->diffInMonths(now());
        $detail = $months >= 12
            ? intdiv($months, 12).'a '.($months % 12).'m'
            : $months.'m';

        return ['value' => $joined->lte(now()->subMonths(3)), 'detail' => $detail];
    }

    /**
     * Capacitaciones: el 100 % de los cursos obligatorios completados.
     *
     * @param  array{joinedAt: array<int,mixed>, obligatorios: int, completados: array<int,int>}  $ctx
     * @return array{value: bool, detail: string}
     */
    private function capacitacionesFor(?int $userId, array $ctx): array
    {
        $total = $ctx['obligatorios'];

        if ($total === 0) {
            return ['value' => true, 'detail' => 'Sin obligatorios'];
        }

        $done = $userId === null ? 0 : ($ctx['completados'][$userId] ?? 0);

        return ['value' => $done >= $total, 'detail' => "{$done}/{$total} cursos"];
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

        // Las manuales arrancan en false; el admin las valida. Las automáticas se calculan.
        foreach ($this->precondiciones()->whereNull('auto_source') as $precondicion) {
            $participant->preconditionStatuses()->firstOrCreate(
                ['precondicion_id' => $precondicion->id],
                ['value' => false],
            );
        }

        return $participant;
    }

    /**
     * Marca las precondiciones manuales de un participante.
     * Las automáticas (auto_source) son derivadas y no se pueden escribir.
     *
     * @param  array<string,bool>  $pre
     *
     * @throws AutoPreconditionException si se intenta escribir una automática.
     */
    public function setPreconditions(SumateParticipant $participant, array $pre): SumateParticipant
    {
        $catalogo = $this->precondiciones()->keyBy('slug');

        foreach (array_keys($pre) as $slug) {
            if (($catalogo[$slug] ?? null)?->auto_source) {
                throw new AutoPreconditionException(
                    "La pre-condición «{$catalogo[$slug]->label}» la calcula el sistema y no se puede editar."
                );
            }
        }

        DB::transaction(function () use ($participant, $pre, $catalogo) {
            foreach ($pre as $slug => $value) {
                if (! isset($catalogo[$slug])) {
                    continue;
                }

                $participant->preconditionStatuses()->updateOrCreate(
                    ['precondicion_id' => $catalogo[$slug]->id],
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
    public function summary(SumateParticipant $participant, ?array $ctx = null): array
    {
        $ctx ??= $this->autoContext();
        $participant->loadMissing(['actionCounts.accion', 'preconditionStatuses.precondicion']);

        $acc = [];
        foreach ($participant->actionCounts as $ac) {
            if ($ac->accion) {
                $acc[$ac->accion->slug] = $ac->count;
            }
        }

        ['pre' => $pre, 'autoDetail' => $autoDetail] = $this->preconditionsFor($participant, $ctx);
        $level = $this->levelFor($participant, $ctx);

        return [
            'id' => $participant->id,
            'userId' => $participant->user_id,
            'name' => $participant->name,
            'initials' => $participant->initials,
            'color' => $participant->color,
            'area' => $participant->area,
            'pre' => $pre,
            'autoDetail' => $autoDetail,
            'acc' => $acc,
            'pts' => $this->pointsFor($participant),
            'eligible' => $this->isEligible($participant, $ctx),
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
