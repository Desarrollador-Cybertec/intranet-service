<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'label' => fake()->unique()->words(3, true),
            'icon' => '📘',
            'tag' => 'Desarrollo',
            'tag_color' => '#1565C0',
            'tag_bg' => '#E3F2FD',
            'desc' => fake()->sentence(10),
            'duration' => '4 horas',
            'modality' => 'Virtual',
        ];
    }
}
