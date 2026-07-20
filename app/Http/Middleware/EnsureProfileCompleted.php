<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    /**
     * Bloquea la app para los usuarios importados que aún no completaron su perfil.
     * Uso en rutas: ->middleware('profile.completed')
     *
     * 428 Precondition Required: el front redirige a /completar-perfil.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        if (! $user->isProfileComplete()) {
            return response()->json([
                'message' => 'Debes completar tu perfil antes de continuar.',
                'missingFields' => $user->missingProfileFields(),
            ], 428);
        }

        return $next($request);
    }
}
