<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\SumateConfig;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * Datos agregados para el dashboard (home). Todo es de solo lectura y se calcula
 * a partir de los datos reales ya existentes; no hay tablas propias del dashboard.
 */
class DashboardController extends Controller
{
    /** GET /api/dashboard — KPIs + cumpleaños de hoy. */
    public function summary(): JsonResponse
    {
        $today = Carbon::today();
        $config = SumateConfig::active();

        $birthdaysToday = $this->activeColaboradores()
            ->whereNotNull('birthday')
            ->whereMonth('birthday', $today->month)
            ->whereDay('birthday', $today->day)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'initials' => $u->initials ?: User::initialsFrom($u->name),
                'color' => $u->color ?: User::colorFrom($u->email),
                'area' => $u->area,
                'photo' => $u->photo,
            ]);

        return response()->json([
            'stats' => [
                'colaboradores' => $this->activeColaboradores()->count(),
                'eventosProximos' => Article::type('eventos')
                    ->whereNotNull('event_date')
                    ->whereDate('event_date', '>=', $today)
                    ->count(),
                'cumpleanosMes' => $this->activeColaboradores()
                    ->whereNotNull('birthday')
                    ->whereMonth('birthday', $today->month)
                    ->count(),
                'sumateTrimestre' => $config?->trimestre,
                'sumatePeriodoLabel' => $config?->periodo_label,
            ],
            'birthdaysToday' => $birthdaysToday,
        ]);
    }

    /**
     * GET /api/dashboard/notifications — feed derivado (cumpleaños de hoy +
     * últimas noticias/comunicados + reconocimientos), ordenado por fecha desc.
     */
    public function notifications(): JsonResponse
    {
        $today = Carbon::today();

        $birthdays = $this->activeColaboradores()
            ->whereNotNull('birthday')
            ->whereMonth('birthday', $today->month)
            ->whereDay('birthday', $today->day)
            ->get()
            ->map(fn (User $u) => [
                'id' => "bday-{$u->id}",
                'type' => 'birthday',
                'title' => "🎂 Hoy cumple años {$u->name}",
                'subtitle' => $u->area,
                'date' => $today->toIso8601String(),
                'section' => 'directorio',
            ]);

        $sectionByType = [
            'noticias' => 'enterate',
            'comunicados' => 'enterate',
            'reconocimientos' => 'reconocimientos',
        ];

        $articles = Article::whereIn('type', array_keys($sectionByType))
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (Article $a) => [
                'id' => "art-{$a->id}",
                'type' => $a->type,
                'title' => $a->title,
                'subtitle' => $a->author,
                'date' => $a->created_at?->toIso8601String(),
                'section' => $sectionByType[$a->type],
            ]);

        $items = $birthdays->concat($articles)
            ->sortByDesc('date')
            ->take(15)
            ->values();

        return response()->json(['items' => $items]);
    }

    /** Colaboradores visibles: activos y con perfil completo. */
    private function activeColaboradores()
    {
        return User::query()
            ->where('active', true)
            ->whereNotNull('profile_completed_at');
    }
}
