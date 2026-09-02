<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePermissionMatrixRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\RoleGuardService;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de vistas/acciones + lectura y escritura en bloque de la matriz de permisos.
 * Todas las rutas exigen configuraciones.*.
 */
class PermissionController extends Controller
{
    public function __construct(private readonly RoleGuardService $guard) {}

    public function catalog(): JsonResponse
    {
        return response()->json([
            'items' => Permissions::catalog(),
            'actions' => Permissions::actionCatalog(),
        ]);
    }

    public function matrix(): JsonResponse
    {
        return response()->json($this->matrixPayload());
    }

    public function updateMatrix(UpdatePermissionMatrixRequest $request): JsonResponse
    {
        $rolesInput = $request->validated('roles');
        $roleIds = collect($rolesInput)->pluck('id');
        $roles = Role::whereIn('id', $roleIds)->get()->keyBy('id');

        $overrides = [];
        foreach ($rolesInput as $entry) {
            $role = $roles->get($entry['id']);

            if (! $role) {
                continue;
            }

            if ($role->slug === Role::SUPERADMIN) {
                abort(422, 'El rol Superadministrador siempre tiene todos los permisos.');
            }

            $overrides[$role->id] = in_array('editar', $entry['permissions']['configuraciones'] ?? [], true);
        }

        if ($this->guard->wouldOrphan($overrides)) {
            abort(422, 'Debe quedar al menos un usuario activo que pueda administrar roles y permisos.');
        }

        DB::transaction(function () use ($rolesInput, $roles) {
            foreach ($rolesInput as $entry) {
                $roles->get($entry['id'])?->syncMatrix($entry['permissions']);
            }
        });

        return response()->json($this->matrixPayload());
    }

    /** @return array<string,mixed> */
    private function matrixPayload(): array
    {
        $roles = Role::withCount('users')->with('permissions')->orderBy('position')->get();

        return [
            'views' => Permissions::catalog(),
            'actions' => Permissions::actionCatalog(),
            'roles' => RoleResource::collection($roles),
        ];
    }
}
