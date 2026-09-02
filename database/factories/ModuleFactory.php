<?php

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        return [
            'section' => 'rh',
            'slug' => fake()->unique()->slug(2),
            'type' => 'enlace',
            'label' => fake()->words(3, true),
            'icon' => '📄',
            'color' => '#2E7D32',
            'bg' => '#E8F5E9',
            'desc' => fake()->sentence(10),
            'href' => 'https://insumma.co',
            'config' => null,
            'visible' => true,
            'position' => 0,
        ];
    }
}
