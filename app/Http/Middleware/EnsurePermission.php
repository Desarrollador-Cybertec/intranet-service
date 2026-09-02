<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Verifica que el usuario autenticado tenga la acción dada sobre la vista.
     * Uso en rutas: ->middleware('perm:sst') (implica 'ver') o ->middleware('perm:sst,crear')
     */
    public function handle(Request $request, Closure $next, string $view, string $action = 'ver'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        if (! $user->isSuperadmin() && ! $user->hasPermission($view, $action)) {
            abort(403, $action === 'ver'
                ? 'Esta sección no está habilitada para tu rol.'
                : 'No tienes permiso para realizar esta acción.');
        }

        return $next($request);
    }
}
