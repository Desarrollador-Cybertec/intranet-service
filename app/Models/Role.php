<?php

namespace App\Models;

use App\Support\Permissions;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'protected' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public const SUPERADMIN = 'superadmin';

    public const DEFAULT = 'cualquiera';

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /** Roles que se aplican implícitamente a todo usuario (Cualquiera). */
    public function scopeDefaults($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Mapa vista => acciones que tiene ESTE rol (no incluye lo heredado de otros roles
     * del usuario; para eso ver User::permissions()).
     *
     * @return array<string, list<string>>
     */
    public function matrix(): array
    {
        $map = [];

        foreach ($this->permissions as $p) {
            $map[$p->view][] = $p->action;
        }

        return Permissions::normalize($map);
    }

    /**
     * Reemplaza la matriz completa del rol en una transacción (delete + insert).
     *
     * @param  array<string, list<string>>  $map
     */
    public function syncMatrix(array $map): void
    {
        $normalized = Permissions::normalize($map);

        DB::transaction(function () use ($normalized) {
            $this->permissions()->delete();

            $rows = [];
            foreach ($normalized as $view => $actions) {
                foreach ($actions as $action) {
                    $rows[] = ['role_id' => $this->id, 'view' => $view, 'action' => $action];
                }
            }

            if ($rows !== []) {
                RolePermission::insert($rows);
            }

            $this->touch();
        });
    }
}
