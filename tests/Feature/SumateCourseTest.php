<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SumateCourseTest extends TestCase
{
    use RefreshDatabase;

    private function approver(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'sig-sst')->firstOrFail());

        return $user;
    }

    public function test_index_flags_which_courses_are_mandatory(): void
    {
        Course::factory()->create(['label' => 'Inducción', 'tag' => 'Obligatorio']);
        Course::factory()->create(['label' => 'Excel', 'tag' => 'Técnico']);

        $res = $this->actingAs($this->approver())->getJson('/api/sumate/cursos')->assertOk();

        $byLabel = collect($res->json('items'))->keyBy('label');
        $this->assertTrue($byLabel['Inducción']['obligatorio']);
        $this->assertFalse($byLabel['Excel']['obligatorio']);
    }

    public function test_completions_lists_active_users_with_their_status(): void
    {
        $course = Course::factory()->create();
        $done = User::factory()->create(['name' => 'Ya completó', 'active' => true]);
        User::factory()->create(['name' => 'Sin completar', 'active' => true]);
        User::factory()->create(['name' => 'Usuario Inactivo', 'active' => false]);

        CourseEnrollment::create(['user_id' => $done->id, 'course_id' => $course->id, 'completed' => true]);

        $res = $this->actingAs($this->approver())->getJson("/api/sumate/cursos/{$course->id}/completados")->assertOk();

        $byName = collect($res->json('items'))->keyBy('name');
        $this->assertTrue($byName['Ya completó']['completed']);
        $this->assertFalse($byName['Sin completar']['completed']);
        $this->assertArrayNotHasKey('Usuario Inactivo', $byName->toArray());
    }

    public function test_set_completion_toggles_it(): void
    {
        $course = Course::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($this->approver())->postJson("/api/sumate/cursos/{$course->id}/completados", [
            'userId' => $user->id, 'completed' => true,
        ])->assertOk();

        $this->assertDatabaseHas('course_enrollments', ['user_id' => $user->id, 'course_id' => $course->id, 'completed' => true]);
    }

    public function test_a_baseline_sumate_ver_user_cannot_manage_courses(): void
    {
        $course = Course::factory()->create();
        $bystander = User::factory()->create();
        $bystander->roles()->attach(Role::where('slug', 'rrhh')->firstOrFail());

        $this->actingAs($bystander)->getJson('/api/sumate/cursos')->assertForbidden();
        $this->actingAs($bystander)->getJson("/api/sumate/cursos/{$course->id}/completados")->assertForbidden();
    }

    public function test_import_command_updates_matching_rows_and_skips_the_rest(): void
    {
        $course = Course::factory()->create(['label' => 'Inducción corporativa']);
        $user = User::factory()->create(['email' => 'user@cybertec.com.co']);

        $path = tempnam(sys_get_temp_dir(), 'capacitaciones').'.csv';
        file_put_contents($path, "Correo,Curso,Completado\nuser@cybertec.com.co,Inducción corporativa,Si\nno-existe@insumma.co,Inducción corporativa,Si\nuser@cybertec.com.co,Curso Que No Existe,Si\n");

        Artisan::call('sumate:import-capacitaciones', ['file' => $path]);
        $output = Artisan::output();

        unlink($path);

        $this->assertDatabaseHas('course_enrollments', ['user_id' => $user->id, 'course_id' => $course->id, 'completed' => true]);
        $this->assertStringContainsString('Correo no encontrado', $output);
        $this->assertStringContainsString('Curso no encontrado', $output);
    }

    public function test_import_command_dry_run_writes_nothing(): void
    {
        $course = Course::factory()->create(['label' => 'Inducción corporativa']);
        $user = User::factory()->create(['email' => 'user@cybertec.com.co']);

        $path = tempnam(sys_get_temp_dir(), 'capacitaciones').'.csv';
        file_put_contents($path, "Correo,Curso,Completado\nuser@cybertec.com.co,Inducción corporativa,Si\n");

        Artisan::call('sumate:import-capacitaciones', ['file' => $path, '--dry-run' => true]);
        unlink($path);

        $this->assertDatabaseMissing('course_enrollments', ['user_id' => $user->id, 'course_id' => $course->id]);
    }
}
