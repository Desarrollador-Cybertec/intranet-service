<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapacitacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_is_relative_to_user(): void
    {
        $user = User::factory()->create(['name' => 'U', 'email' => 'u@x.co', 'password' => 'secret123', 'role_type' => 'user']);

        $courses = collect(range(1, 4))->map(fn ($i) => Course::create([
            'label' => "Curso $i", 'icon' => '📘', 'tag' => 'Obligatorio', 'tag_color' => '#000', 'tag_bg' => '#fff',
            'desc' => 'd', 'duration' => '4 horas', 'modality' => 'Virtual',
        ]));

        // El usuario completó 1 de 4.
        $user->enrollments()->create(['course_id' => $courses->first()->id, 'completed' => true]);

        $this->actingAs($user)->getJson('/api/capacitaciones')
            ->assertOk()
            ->assertJsonPath('progress.total', 4)
            ->assertJsonPath('progress.done', 1)
            ->assertJsonPath('progress.percent', 25);
    }

    public function test_enrollment_returns_success(): void
    {
        $user = User::factory()->create(['name' => 'U', 'email' => 'u@x.co', 'password' => 'secret123', 'role_type' => 'user']);
        $course = Course::create([
            'label' => 'C', 'icon' => '📘', 'tag' => 'Obligatorio', 'tag_color' => '#000', 'tag_bg' => '#fff',
            'desc' => 'd', 'duration' => '4 horas', 'modality' => 'Virtual',
        ]);

        $this->actingAs($user)->postJson("/api/capacitaciones/{$course->id}/inscripcion")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('id', $course->id);
    }
}
