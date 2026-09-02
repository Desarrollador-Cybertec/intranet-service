<?php

namespace Database\Factories;

use App\Models\DirectoryPerson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DirectoryPerson>
 */
class DirectoryPersonFactory extends Factory
{
    protected $model = DirectoryPerson::class;

    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'name' => $name,
            'area' => fake()->randomElement(['Comercial', 'Bioseguridad', 'Gestión Humana', 'TI']),
            'role' => fake()->jobTitle(),
            'phone' => fake()->numerify('3#########'),
            'extension' => null,
            'email' => fake()->unique()->safeEmail(),
            'user_id' => null,
            'photo' => null,
            'initials' => User::initialsFrom($name),
            'color' => User::colorFrom($name),
            'active' => true,
            'position' => 0,
        ];
    }
}
