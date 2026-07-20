<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActive
{
    /**
     * Corta el acceso de las cuentas desactivadas por un administrador.
     * Al desactivar se revocan los tokens, pero esto cubre cualquier token vivo.
     * Uso en rutas: ->middleware('active')
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        if (! $user->active) {
            abort(403, 'Tu cuenta está desactivada. Comunícate con Gestión Humana.');
        }

        return $next($request);
    }
}
