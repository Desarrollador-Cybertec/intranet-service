<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'role_type', 'initials', 'area', 'phone', 'color', 'joined_at', 'birthday', 'extension', 'photo', 'profile_completed_at', 'active'])]
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
     * Dominio cuyos administradores pueden gestionar roles (asignar/retirar admin).
     * El resto de administradores gestiona contenido y perfiles, pero no roles.
     */
    public const ROLE_MANAGER_DOMAIN = 'cybertec.com.co';

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
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role_type === 'admin';
    }

    /**
     * Solo los administradores con correo del dominio gestor pueden cambiar roles
     * (promover/retirar administradores). Ver UserController::guardRoleChange.
     */
    public function canManageRoles(): bool
    {
        return $this->isAdmin()
            && Str::endsWith(Str::lower((string) $this->email), '@'.self::ROLE_MANAGER_DOMAIN);
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
