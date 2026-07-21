<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DirectoryPersonResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * El Directorio es una proyección de solo lectura de los usuarios: muestra a los
 * colaboradores activos con perfil completo. La gestión de personas (alta, edición,
 * baja) se hace en la administración de usuarios (UserController, /api/users).
 */
class DirectoryController extends Controller
{
    /** GET /api/directory?search=&area= */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->where('active', true)
            ->whereNotNull('profile_completed_at');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%");
            });
        }

        if ($area = $request->query('area')) {
            $query->where('area', $area);
        }

        return $this->items(DirectoryPersonResource::collection($query->orderBy('name')->get()));
    }
}
