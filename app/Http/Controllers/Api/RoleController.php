<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\RoleGuardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Administración de roles · todas las rutas exigen configuraciones.*.
 */
class RoleController extends Controller
{
    public function __construct(private readonly RoleGuardService $guard) {}

    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')->orderBy('position')->get();

        return $this->items(RoleResource::collection($roles));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = Role::create([
            'slug' => $this->uniqueSlug($data['name']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'protected' => false,
            'is_default' => false,
            'position' => (Role::max('position') ?? 0) + 10,
        ]);

        if (! empty($data['permissions'])) {
            $role->syncMatrix($data['permissions']);
        }

        return (new RoleResource($role->load('permissions')))->response()->setStatusCode(201);
    }

    public function show(Role $role): RoleResource
    {
        return new RoleResource($role->loadCount('users')->load('permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $data = $request->validated();

        if ($role->protected && (array_key_exists('name', $data) || array_key_exists('description', $data))) {
            abort(422, 'Los roles del sistema no se pueden renombrar.');
        }

        $role->fill($data)->save();

        return new RoleResource($role->load('permissions'));
    }

    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role): RoleResource
    {
        if ($role->slug === Role::SUPERADMIN) {
            abort(422, 'El rol Superadministrador siempre tiene todos los permisos.');
        }

        $newPermissions = $request->validated('permissions');
        $grantsConfigEditor = in_array('editar', $newPermissions['configuraciones'] ?? [], true);

        if ($this->guard->wouldOrphan([$role->id => $grantsConfigEditor])) {
            abort(422, 'Debe quedar al menos un usuario activo que pueda administrar roles y permisos.');
        }

        $role->syncMatrix($newPermissions);

        return new RoleResource($role->load('permissions'));
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->protected) {
            abort(422, 'Este rol es del sistema y no puede eliminarse.');
        }

        if ($this->guard->wouldOrphan([$role->id => false])) {
            abort(422, 'Debe quedar al menos un usuario activo que pueda administrar roles y permisos.');
        }

        $role->delete();

        return response()->json(['success' => true]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Role::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
