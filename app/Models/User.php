<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use App\Support\Permissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'role_type', 'initials', 'area', 'phone', 'color', 'joined_at', 'birthday', 'extension', 'photo', 'profile_completed_at', 'active', 'activated_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Campos que un usuario debe tener llenos para operar en la intranet.
     * `initials` y `color` se derivan solos; `extension` es opcional (no todos tienen).
     *
     * @var list<string>
     */
    public const REQUIRED_PROFILE_FIELDS = ['name', 'role', 'area', 'phone', 'joined_at'];

    /** Paleta usada en los seeders y en los avatares del front. */
    private const COLORS = ['#2E7D32', '#1565C0', '#F57C00', '#C62828', '#6A1B9A'];

    /**
     * Sin esto, un usuario recién creado tiene `active` en null hasta releerlo de
     * la base de datos, y EnsureActive lo tomaría por desactivado.
     *
     * @var array<string,mixed>
     */
    protected $attributes = [
        'active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'joined_at' => 'date',
            'birthday' => 'date',
            'profile_completed_at' => 'datetime',
            'active' => 'boolean',
            'activated_at' => 'datetime',
        ];
    }

    /** @deprecated usar isSuperadmin() o hasPermission($vista,$accion). Se conserva para EnsureRole/tests legados. */
    public function isAdmin(): bool
    {
        return $this->role_type === 'admin';
    }

    /** El SPA vive en un origen distinto a esta API: el enlace debe apuntar allá, no a una ruta web de Laravel. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps()->orderBy('roles.position');
    }

    public function isSuperadmin(): bool
    {
        return $this->roles->contains('slug', Role::SUPERADMIN);
    }

    public function hasPermission(string $view, string $action = 'ver'): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return in_array($action, $this->permissions()[$view] ?? [], true);
    }

    /**
     * Mapa vista => acciones, unión de todos los roles del usuario + el/los rol(es)
     * `is_default` (Cualquiera), memoizado por instancia (no en caché: un cambio de
     * matriz debe verse en la siguiente petición, no esperar a que expire un TTL).
     *
     * @return array<string, list<string>>
     */
    public function permissions(): array
    {
        if ($this->permissionsCache !== null) {
            return $this->permissionsCache;
        }

        if ($this->isSuperadmin()) {
            return $this->permissionsCache = Permissions::all();
        }

        $roleIds = $this->roles->pluck('id')
            ->merge(Role::defaults()->pluck('id'))
            ->unique();

        $map = [];
        foreach (RolePermission::whereIn('role_id', $roleIds)->get(['view', 'action']) as $p) {
            $map[$p->view][] = $p->action;
        }

        return $this->permissionsCache = Permissions::normalize($map);
    }

    /**
     * Usuarios que tienen la habilidad dada, para resolver destinatarios (p. ej. quién
     * recibe la notificación de un registro pendiente). No filtra por `active`: eso lo
     * decide quien llama.
     */
    public function scopeWithPermission(Builder $query, string $view, string $action = 'ver'): Builder
    {
        $grantedByDefault = Role::defaults()
            ->whereHas('permissions', fn ($p) => $p->where('view', $view)->where('action', $action))
            ->exists();

        // Cualquiera ya lo concede: todo el mundo lo tiene, no hace falta filtrar.
        if ($grantedByDefault) {
            return $query;
        }

        return $query->where(function ($q) use ($view, $action) {
            $q->whereHas('roles', fn ($r) => $r->where('slug', Role::SUPERADMIN))
                ->orWhereHas('roles.permissions', fn ($p) => $p->where('view', $view)->where('action', $action));
        });
    }

    private ?array $permissionsCache = null;

    /**
     * Quién puede administrar roles y asignaciones de rol (ver UserController::guardRoleChange
     * y PUT /api/users/{user}/roles). Antes era un dominio de correo hardcodeado; ahora es
     * el mismo permiso que abre Configuraciones — administrar quién tiene qué acceso es,
     * precisamente, administrar la configuración de permisos.
     */
    public function canManageRoles(): bool
    {
        return $this->hasPermission('configuraciones', 'editar');
    }

    /**
     * Campos obligatorios que siguen vacíos, en camelCase (shape del contrato API).
     *
     * @return list<string>
     */
    public function missingProfileFields(): array
    {
        $missing = [];

        foreach (self::REQUIRED_PROFILE_FIELDS as $field) {
            $value = $this->getAttribute($field);

            if ($value === null || (is_string($value) && trim($value) === '')) {
                $missing[] = Str::camel($field);
            }
        }

        return $missing;
    }

    public function isProfileComplete(): bool
    {
        return $this->missingProfileFields() === [];
    }

    /** Iniciales a partir de las dos primeras palabras del nombre. */
    public static function initialsFrom(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : '??';
    }

    /** Color de avatar determinístico (el mismo usuario siempre obtiene el mismo). */
    public static function colorFrom(string $seed): string
    {
        return self::COLORS[crc32(Str::lower($seed)) % count(self::COLORS)];
    }

    public function forumPosts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'author_id');
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class, 'author_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function sumateParticipant()
    {
        return $this->hasOne(SumateParticipant::class);
    }
}
