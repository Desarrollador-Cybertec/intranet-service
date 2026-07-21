<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Perfil completo por defecto: si no, EnsureProfileCompleted responde 428.
            'role' => 'Colaborador',
            'area' => fake()->randomElement(['Comercial', 'TI', 'Gestión Humana', 'Logística']),
            'phone' => fake()->numerify('3#########'),
            'initials' => User::initialsFrom($name),
            'joined_at' => fake()->dateTimeBetween('-3 years')->format('Y-m-d'),
            'profile_completed_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Usuario recién importado de la nómina: solo correo y contraseña.
     * Debe pasar por /auth/me antes de poder usar el resto de la API.
     */
    public function imported(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => null,
            'area' => null,
            'phone' => null,
            'joined_at' => null,
            'profile_completed_at' => null,
        ]);
    }

    /** Cuenta desactivada por un administrador: no puede iniciar sesión. */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['active' => false]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_type' => 'admin',
            'role' => 'Administrador',
            // Los administradores del sistema pertenecen al dominio gestor: solo ellos
            // pueden cambiar roles (ver User::canManageRoles). Sobrescribe el email si
            // necesitas un admin de otro dominio (p. ej. un @insumma.co promovido).
            'email' => fake()->unique()->userName().'@'.User::ROLE_MANAGER_DOMAIN,
        ]);
    }
}
