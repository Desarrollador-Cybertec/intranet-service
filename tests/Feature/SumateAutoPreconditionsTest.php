<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\SumateParticipant;
use App\Models\User;
use App\Services\SumateService;
use Database\Seeders\CourseSeeder;
use Database\Seeders\SumateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Antigüedad y capacitaciones las deriva el servidor: Gestión Humana no las marca.
 */
class SumateAutoPreconditionsTest extends TestCase
{
    use RefreshDatabase;

    private SumateService $sumate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sumate = app(SumateService::class);

        // SumateSeeder exige que existan estos dos usuarios.
        User::factory()->create(['email' => 'user@cybertec.com.co', 'name' => 'Usuario Cybertec']);
        User::factory()->create(['email' => 'admin@cybertec.com.co', 'name' => 'Admin Cybertec', 'role_type' => 'admin']);

        $this->seed(CourseSeeder::class);
        $this->seed(SumateSeeder::class);
    }

    private function participantFor(User $user): SumateParticipant
    {
        return $this->sumate->syncParticipantFor($user);
    }

    private function preFor(User $user): array
    {
        return $this->sumate->preconditionsFor($this->participantFor($user))['pre'];
    }

    // ── Antigüedad ───────────────────────────────────────────────────────────

    public function test_antiguedad_is_true_past_three_months(): void
    {
        $user = User::factory()->create(['joined_at' => now()->subMonths(4)]);

        $this->assertTrue($this->preFor($user)['antiguedad']);
    }

    public function test_antiguedad_is_false_for_a_recent_hire(): void
    {
        $user = User::factory()->create(['joined_at' => now()->subMonth()]);

        $this->assertFalse($this->preFor($user)['antiguedad']);
    }

    public function test_antiguedad_is_false_without_a_join_date(): void
    {
        $user = User::factory()->imported()->create();

        $this->assertFalse($this->preFor($user)['antiguedad']);
    }

    public function test_antiguedad_reports_the_elapsed_time_as_detail(): void
    {
        $user = User::factory()->create(['joined_at' => now()->subMonths(16)]);

        $detail = $this->sumate->preconditionsFor($this->participantFor($user))['autoDetail'];

        $this->assertSame('1a 4m', $detail['antiguedad']);
    }

    // ── Capacitaciones ───────────────────────────────────────────────────────

    public function test_capacitaciones_is_true_only_with_every_mandatory_course_done(): void
    {
        $user = User::factory()->create();
        $obligatorios = Course::where('tag', 'Obligatorio')->pluck('id');

        $this->assertGreaterThan(1, $obligatorios->count(), 'El seed debe traer varios obligatorios.');

        // Todos menos uno → sigue en false.
        foreach ($obligatorios->slice(0, -1) as $courseId) {
            $user->enrollments()->updateOrCreate(['course_id' => $courseId], ['completed' => true]);
        }
        $this->assertFalse($this->preFor($user)['capacitaciones']);

        // El último → pasa a true, sin intervención del admin.
        $user->enrollments()->updateOrCreate(['course_id' => $obligatorios->last()], ['completed' => true]);
        $this->assertTrue($this->preFor($user)['capacitaciones']);
    }

    public function test_enrolled_but_not_completed_does_not_count(): void
    {
        $user = User::factory()->create();

        foreach (Course::where('tag', 'Obligatorio')->pluck('id') as $courseId) {
            $user->enrollments()->updateOrCreate(['course_id' => $courseId], ['completed' => false]);
        }

        $this->assertFalse($this->preFor($user)['capacitaciones']);
    }

    public function test_optional_courses_do_not_count_toward_the_requirement(): void
    {
        $user = User::factory()->create();

        foreach (Course::where('tag', '!=', 'Obligatorio')->pluck('id') as $courseId) {
            $user->enrollments()->updateOrCreate(['course_id' => $courseId], ['completed' => true]);
        }

        $this->assertFalse($this->preFor($user)['capacitaciones']);
    }

    public function test_capacitaciones_reports_progress_as_detail(): void
    {
        $user = User::factory()->create();
        $obligatorios = Course::where('tag', 'Obligatorio')->pluck('id');
        $user->enrollments()->updateOrCreate(['course_id' => $obligatorios->first()], ['completed' => true]);

        $detail = $this->sumate->preconditionsFor($this->participantFor($user))['autoDetail'];

        $this->assertSame('1/'.$obligatorios->count().' cursos', $detail['capacitaciones']);
    }

    // ── Contrato y escritura ─────────────────────────────────────────────────

    public function test_the_catalog_flags_which_preconditions_are_automatic(): void
    {
        $res = $this->actingAs(User::factory()->create())
            ->getJson('/api/sumate/participants')
            ->assertOk();

        $auto = collect($res->json('precondiciones'))->pluck('auto', 'id');

        $this->assertTrue($auto['antiguedad']);
        $this->assertTrue($auto['capacitaciones']);
        $this->assertFalse($auto['puntualidad']);
        $this->assertFalse($auto['asistencia']);
        $this->assertFalse($auto['disciplinarios']);
    }

    public function test_admin_cannot_hand_edit_an_automatic_precondition(): void
    {
        $admin = User::factory()->create(['role_type' => 'admin']);
        $target = $this->participantFor(User::factory()->create());

        $this->actingAs($admin)
            ->patchJson("/api/sumate/participants/{$target->id}/precondiciones", [
                'pre' => ['antiguedad' => true],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'La pre-condición «Antigüedad» la calcula el sistema y no se puede editar.');
    }

    public function test_manual_preconditions_are_still_editable(): void
    {
        $admin = User::factory()->create(['role_type' => 'admin']);
        $target = $this->participantFor(User::factory()->create());

        $this->actingAs($admin)
            ->patchJson("/api/sumate/participants/{$target->id}/precondiciones", [
                'pre' => ['puntualidad' => true, 'asistencia' => true, 'disciplinarios' => true],
            ])
            ->assertOk()
            ->assertJsonPath('pre.puntualidad', true);
    }

    // ── Elegibilidad combinada ───────────────────────────────────────────────

    public function test_eligibility_needs_both_automatic_and_manual_preconditions(): void
    {
        $admin = User::factory()->create(['role_type' => 'admin']);
        $user = User::factory()->create(['joined_at' => now()->subYear()]);
        $participant = $this->participantFor($user);

        // Las 3 manuales validadas, pero faltan las capacitaciones → no elegible.
        $this->sumate->setPreconditions($participant, [
            'puntualidad' => true, 'asistencia' => true, 'disciplinarios' => true,
        ]);
        $this->assertFalse($this->sumate->isEligible($participant->fresh()));

        $this->actingAs($admin)->postJson('/api/sumate/acciones', [
            'participantId' => $participant->id, 'accionId' => 'yoAporto', 'delta' => 1,
        ])->assertStatus(422);

        // Al completar los obligatorios se vuelve elegible sin tocar nada más.
        foreach (Course::where('tag', 'Obligatorio')->pluck('id') as $courseId) {
            $user->enrollments()->updateOrCreate(['course_id' => $courseId], ['completed' => true]);
        }

        $this->actingAs($admin)->postJson('/api/sumate/acciones', [
            'participantId' => $participant->id, 'accionId' => 'yoAporto', 'delta' => 1,
        ])->assertOk()->assertJsonPath('eligible', true);
    }
}
