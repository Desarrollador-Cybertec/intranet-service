<?php

namespace App\Services;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;

/**
 * Guardrail transversal: la intranet nunca debe quedar sin ningún usuario activo
 * capaz de administrar roles y permisos (configuraciones.editar). Se consulta ANTES
 * de persistir cualquier cambio que pudiera dejar el sistema sin ese usuario.
 */
class RoleGuardService
{
    private const VIEW = 'configuraciones';

    private const ACTION = 'editar';

    /** ¿Ya lo tiene todo el mundo vía el rol por defecto (Cualquiera)? */
    public function grantedToEveryoneByDefault(): bool
    {
        return Role::defaults()
            ->whereHas('permissions', fn ($p) => $p->where('view', self::VIEW)->where('action', self::ACTION))
            ->exists();
    }

    /**
     * ¿Quedaría el sistema sin ningún usuario activo con el permiso si los roles listados
     * en $roleGrantOverrides pasaran a conceder (true) o dejar de conceder (false) el
     * permiso? Los roles no listados conservan su estado real actual. Úsalo para: borrar
     * un rol (override => false), guardar los permisos de un rol, o guardar varios a la vez.
     *
     * @param  array<int,bool>  $roleGrantOverrides
     */
    public function wouldOrphan(array $roleGrantOverrides = []): bool
    {
        if ($this->grantedToEveryoneByDefault()) {
            return false;
        }

        $currentGrantingIds = RolePermission::where('view', self::VIEW)->where('action', self::ACTION)
            ->pluck('role_id')->all();

        $grantingIds = array_values(array_filter(
            array_unique(array_merge($currentGrantingIds, array_keys($roleGrantOverrides))),
            fn ($id) => $roleGrantOverrides[$id] ?? in_array($id, $currentGrantingIds, true),
        ));

        return ! User::where('active', true)
            ->where(fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('slug', Role::SUPERADMIN))
                ->orWhereHas('roles', fn ($r) => $r->whereIn('roles.id', $grantingIds)))
            ->exists();
    }

    /**
     * Caso "reasignar los roles de UN usuario": el resto del sistema no cambia, solo
     * $user pasaría a tener exactamente $newRoleIds.
     *
     * @param  list<int>  $newRoleIds
     */
    public function wouldOrphanBySyncingUserRoles(User $user, array $newRoleIds): bool
    {
        if ($this->grantedToEveryoneByDefault() || ! $user->active) {
            return false;
        }

        $othersHaveIt = User::where('active', true)->whereKeyNot($user->getKey())
            ->where(fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('slug', Role::SUPERADMIN))
                ->orWhereHas('roles.permissions', fn ($p) => $p->where('view', self::VIEW)->where('action', self::ACTION)))
            ->exists();

        if ($othersHaveIt) {
            return false;
        }

        $selfCovers = Role::whereIn('id', $newRoleIds)
            ->where(fn ($q) => $q->where('slug', Role::SUPERADMIN)
                ->orWhereHas('permissions', fn ($p) => $p->where('view', self::VIEW)->where('action', self::ACTION)))
            ->exists();

        return ! $selfCovers;
    }

    /** Caso "desactivar a este usuario": ¿queda alguien más activo con el permiso? */
    public function wouldOrphanByDeactivating(User $user): bool
    {
        if ($this->grantedToEveryoneByDefault() || ! $user->active) {
            return false;
        }

        if (! $user->isSuperadmin() && ! $user->hasPermission(self::VIEW, self::ACTION)) {
            return false; // este usuario no era uno de los que sostienen el permiso
        }

        return ! User::where('active', true)->whereKeyNot($user->getKey())
            ->where(fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('slug', Role::SUPERADMIN))
                ->orWhereHas('roles.permissions', fn ($p) => $p->where('view', self::VIEW)->where('action', self::ACTION)))
            ->exists();
    }
}
