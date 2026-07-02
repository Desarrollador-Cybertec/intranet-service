<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Envuelve una colección de recursos en la convención del contrato: { "items": [...] }.
     */
    protected function items($resourceCollection, array $extra = []): JsonResponse
    {
        return response()->json(['items' => $resourceCollection] + $extra);
    }
}
