<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\SyncUserRolesRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserAdminResource;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AccountActivatedNotification;
use App\Services\DirectoryService;
use App\Services\RoleGuardService;
use App\Services\SumateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

/**
 * Administración de usuarios · todas las rutas exigen role:admin.
 * La carga masiva inicial se hace con `php artisan users:import`.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly SumateService $sumate,
        private readonly RoleGuardService $roleGuard,
        private readonly DirectoryService $directory,
    ) {}

    /**
     * GET /api/users · admin — listado paginado con filtros.
     * Filtros: q (nombre/correo), roleType, area, status (active|inactive|incomplete).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'roleType' => ['sometimes', 'nullable', 'in:admin,user'],
            'roleSlug' => ['sometimes', 'nullable', 'string', 'exists:roles,slug'],
            'area' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'in:active,inactive,incomplete'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $users = User::query()
            ->with(['sumateParticipant', 'roles'])
            ->when($data['q'] ?? null, fn ($query, $q) => $query->where(
                fn ($sub) => $sub->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"),
            ))
            ->when($data['roleType'] ?? null, fn ($query, $role) => $query->where('role_type', $role))
            ->when($data['roleSlug'] ?? null, fn ($query, $slug) => $query->whereHas(
                'roles', fn ($r) => $r->where('slug', $slug),
            ))
            ->when($data['area'] ?? null, fn ($query, $area) => $query->where('area', $area))
            ->when($data['status'] ?? null, fn ($query, $status) => match ($status) {
                'active' => $query->where('active', true),
                'inactive' => $query->where('active', false),
                'incomplete' => $query->whereNull('profile_completed_at'),
            })
            ->orderBy('name')
            ->paginate($data['perPage'] ?? 25);

        return UserAdminResource::collection($users);
    }

    /** POST /api/users · admin — alta manual. */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Crear un administrador es asignar un rol: reservado al dominio gestor.
        if ($data['roleType'] === 'admin' && ! $request->user()->canManageRoles()) {
            abort(403, 'No tienes permiso para crear administradores.');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'password' => $data['password'], // el cast 'hashed' lo encripta
            'role_type' => $data['roleType'],
            'role' => $data['role'] ?? null,
            'area' => $data['area'] ?? null,
            'phone' => $data['phone'] ?? null,
            'joined_at' => $data['joinedAt'] ?? null,
            'birthday' => $data['birthday'] ?? null,
            'extension' => $data['extension'] ?? null,
            'photo' => $data['photo'] ?? null,
            'initials' => User::initialsFrom($data['name']),
            'color' => User::colorFrom($data['email']),
            'active' => true,
            'activated_at' => now(),
        ]);

        if ($user->isProfileComplete()) {
            $user->forceFill(['profile_completed_at' => now()])->save();
        }

        if (! empty($data['roleSlugs'])) {
            $this->syncRoles($user, $data['roleSlugs']);
        }

        $this->sumate->syncParticipantFor($user);
        $this->directory->syncFromUser($user);

        return (new UserAdminResource($user->load(['sumateParticipant', 'roles'])))
            ->response()
            ->setStatusCode(201);
    }

    /** PATCH /api/users/{user} · admin — perfil, rol y estado de la cuenta. */
    public function update(UpdateUserRequest $request, User $user): UserAdminResource
    {
        $data = $request->validated();
        $self = $request->user();

        $this->guardRoleChange($data, $user, $self);
        $this->guardDeactivation($data, $user, $self);

        $roleSlugs = $data['roleSlugs'] ?? null;
        unset($data['roleSlugs']);

        if (array_key_exists('joinedAt', $data)) {
            $data['joined_at'] = $data['joinedAt'];
            unset($data['joinedAt']);
        }

        if (array_key_exists('roleType', $data)) {
            $data['role_type'] = $data['roleType'];
            unset($data['roleType']);
        }

        if (array_key_exists('email', $data)) {
            $data['email'] = Str::lower($data['email']);
        }

        $wasActive = (bool) $user->getOriginal('active');
        $user->fill($data);
        $justActivated = array_key_exists('active', $data) && $user->active && ! $wasActive;

        if ($justActivated && ! $user->activated_at) {
            $user->activated_at = now();
        }

        if (array_key_exists('name', $data)) {
            $user->initials = User::initialsFrom($user->name);
        }

        // Completar el perfil desde aquí también levanta el bloqueo del onboarding.
        $user->profile_completed_at = $user->isProfileComplete()
            ? ($user->profile_completed_at ?? now())
            : null;

        $user->save();

        // Al desactivar, cerrar la sesión en todos sus dispositivos.
        if ($user->wasChanged('active') && ! $user->active) {
            $user->tokens()->delete();
        }

        if ($justActivated) {
            $this->directory->syncFromUser($user);
            $user->notify(new AccountActivatedNotification);
        }

        if ($roleSlugs !== null) {
            $this->syncRoles($user, $roleSlugs);
        }

        $this->sumate->syncParticipantFor($user);

        return new UserAdminResource($user->load(['sumateParticipant', 'roles']));
    }

    /** PUT /api/users/{user}/roles · admin — reemplaza el conjunto de roles del usuario. */
    public function setRoles(SyncUserRolesRequest $request, User $user): UserAdminResource
    {
        $this->syncRoles($user, $request->validated('roleSlugs'));

        return new UserAdminResource($user->load(['sumateParticipant', 'roles']));
    }

    /** POST /api/users/{user}/password · admin — restablece la contraseña. */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'max:255'],
        ]);

        $password = $data['password'] ?? Str::password(12, symbols: false);

        $user->forceFill(['password' => $password])->save();
        $user->tokens()->delete();

        // Única vez que la contraseña viaja en claro: el admin se la entrega al colaborador.
        return response()->json(['password' => $password]);
    }

    /**
     * Un admin no puede quitarse el rol a sí mismo, ni dejar el sistema sin administradores.
     *
     * @param  array<string,mixed>  $data
     */
    private function guardRoleChange(array $data, User $user, User $self): void
    {
        if (! array_key_exists('roleType', $data) || $data['roleType'] === $user->role_type) {
            return;
        }

        if (! $self->canManageRoles()) {
            abort(403, 'No tienes permiso para cambiar roles.');
        }

        if ($user->is($self)) {
            abort(422, 'No puedes cambiar tu propio rol. Pídeselo a otro administrador.');
        }

        if ($data['roleType'] === 'user' && $this->isLastActiveAdmin($user)) {
            abort(422, 'Es el último administrador activo: asigna otro antes de quitarle el rol.');
        }
    }

    /**
     * Nadie puede desactivarse a sí mismo ni dejar el sistema sin administradores activos.
     *
     * @param  array<string,mixed>  $data
     */
    private function guardDeactivation(array $data, User $user, User $self): void
    {
        if (! array_key_exists('active', $data) || $data['active'] || ! $user->active) {
            return;
        }

        if ($user->is($self)) {
            abort(422, 'No puedes desactivar tu propia cuenta.');
        }

        if ($this->isLastActiveAdmin($user)) {
            abort(422, 'Es el último administrador activo: asigna otro antes de desactivarlo.');
        }

        // Además del flag legado role_type, cubre a quien administra por un rol RBAC.
        if ($this->roleGuard->wouldOrphanByDeactivating($user)) {
            abort(422, 'Debe quedar al menos un usuario activo que pueda administrar roles y permisos.');
        }
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if ($user->role_type !== 'admin' || ! $user->active) {
            return false;
        }

        return User::where('role_type', 'admin')
            ->where('active', true)
            ->whereKeyNot($user->getKey())
            ->doesntExist();
    }

    /**
     * @param  list<string>  $slugs
     */
    private function syncRoles(User $user, array $slugs): void
    {
        if (in_array(Role::DEFAULT, $slugs, true)) {
            abort(422, 'El rol Cualquiera aplica a todos los usuarios automáticamente.');
        }

        $roleIds = Role::whereIn('slug', $slugs)->pluck('id');

        if ($this->roleGuard->wouldOrphanBySyncingUserRoles($user, $roleIds->all())) {
            abort(422, 'Debe quedar al menos un usuario activo que pueda administrar roles y permisos.');
        }

        $user->roles()->sync($roleIds);
    }
}
