<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;

/**
 * Eventos = Article de type 'eventos'. El CRUD lo maneja ArticleController;
 * aquí solo la inscripción del usuario actual.
 */
class EventController extends Controller
{
    /** POST /api/events/{article}/inscripcion · user */
    public function inscripcion(Article $article): JsonResponse
    {
        if ($article->type !== 'eventos') {
            return response()->json(['message' => 'Recurso no encontrado.'], 404);
        }

        // Registro de inscripción (persistencia detallada fuera de alcance de esta entrega).
        return response()->json(['success' => true]);
    }
}
