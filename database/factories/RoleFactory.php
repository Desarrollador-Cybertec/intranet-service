<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = 'Rol de prueba '.fake()->unique()->word();

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'description' => null,
            'protected' => false,
            'is_default' => false,
            'position' => fake()->numberBetween(100, 900),
        ];
    }

    public function protected(): static
    {
        return $this->state(fn () => ['protected' => true]);
    }

    public function default(): static
    {
        return $this->state(fn () => ['protected' => true, 'is_default' => true]);
    }
}
