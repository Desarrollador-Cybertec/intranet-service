<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\SumateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SumateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // joined_at fijo: la antigüedad es una pre-condición automática (> 3 meses).
        $user = User::factory()->create(['email' => 'user@cybertec.com.co', 'name' => 'Usuario Cybertec', 'role_type' => 'user', 'initials' => 'UC', 'area' => 'Comercial', 'joined_at' => now()->subYears(2)]);
        $admin = User::factory()->create(['email' => 'admin@cybertec.com.co', 'name' => 'Administrador Cybertec', 'role_type' => 'admin', 'initials' => 'AC', 'area' => 'TI', 'joined_at' => now()->subYears(2)]);

        $this->seed(CourseSeeder::class);
        $this->seed(SumateSeeder::class);

        // Capacitaciones también es automática: ambos completan los obligatorios.
        // La no-elegibilidad del admin viene de 'disciplinarios' (manual), vía SumateSeeder.
        foreach ([$user, $admin] as $u) {
            foreach (Course::where('tag', 'Obligatorio')->pluck('id') as $courseId) {
                $u->enrollments()->updateOrCreate(['course_id' => $courseId], ['completed' => true]);
            }
        }
    }

    private function user(): User
    {
        return User::where('email', 'user@cybertec.com.co')->first();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@cybertec.com.co')->first();
    }

    public function test_points_and_eligibility_are_computed_server_side(): void
    {
        $res = $this->actingAs($this->user())->getJson('/api/sumate/participants')->assertOk();

        $byName = collect($res->json('participantes'))->keyBy('name');

        // Usuario Cybertec: todas las acciones al tope → 100 pts, elegible.
        $this->assertSame(100, $byName['Usuario Cybertec']['pts']);
        $this->assertTrue($byName['Usuario Cybertec']['eligible']);

        // Administrador Cybertec: disciplinarios=false → NO elegible.
        $this->assertFalse($byName['Administrador Cybertec']['eligible']);

        $this->assertSame(1, $res->json('myParticipantId'));
    }

    public function test_own_participant_is_linked_when_logged_in_as_user(): void
    {
        $res = $this->actingAs($this->user())->getJson('/api/sumate/participants')->assertOk();

        $res->assertJsonPath('myParticipantId', 1);
        $this->assertSame(100, $res->json('leaderboard.myPoints'));
        $this->assertNotNull($res->json('leaderboard.myRank'));
    }

    public function test_admin_grants_action_respecting_max_and_recomputing(): void
    {
        $admin = $this->admin();
        $target = $this->user()->sumateParticipant;

        // Usuario Cybertec ya está al tope de yoAporto (3): un −1 lo baja a 2.
        $this->actingAs($admin)->postJson('/api/sumate/acciones', [
            'participantId' => $target->id, 'accionId' => 'yoAporto', 'delta' => -1,
        ])->assertOk()->assertJsonPath('acc.yoAporto', 2);

        // +1 vuelve a 3 y otro +1 no supera el max.
        $this->actingAs($admin)->postJson('/api/sumate/acciones', [
            'participantId' => $target->id, 'accionId' => 'yoAporto', 'delta' => 1,
        ])->assertOk()->assertJsonPath('acc.yoAporto', 3);

        $this->actingAs($admin)->postJson('/api/sumate/acciones', [
            'participantId' => $target->id, 'accionId' => 'yoAporto', 'delta' => 1,
        ])->assertOk()->assertJsonPath('acc.yoAporto', 3); // tope respetado
    }

    public function test_regular_user_cannot_grant_actions(): void
    {
        $participant = $this->user()->sumateParticipant;

        $this->actingAs($this->user())->postJson('/api/sumate/acciones', [
            'participantId' => $participant->id, 'accionId' => 'yoAporto', 'delta' => 1,
        ])->assertForbidden();
    }

    public function test_cannot_grant_points_to_a_non_eligible_participant(): void
    {
        // Administrador Cybertec tiene disciplinarios=false → no elegible.
        $target = $this->admin()->sumateParticipant;

        $this->actingAs($this->admin())->postJson('/api/sumate/acciones', [
            'participantId' => $target->id, 'accionId' => 'yoAporto', 'delta' => 1,
        ])->assertStatus(422);
    }

    public function test_admin_validates_preconditions_and_unlocks_eligibility(): void
    {
        $target = $this->admin()->sumateParticipant;

        $this->actingAs($this->admin())
            ->patchJson("/api/sumate/participants/{$target->id}/precondiciones", [
                'pre' => ['disciplinarios' => true],
            ])
            ->assertOk()
            ->assertJsonPath('eligible', true);

        // Ya elegible: ahora sí se le pueden otorgar puntos.
        $this->actingAs($this->admin())->postJson('/api/sumate/acciones', [
            'participantId' => $target->id, 'accionId' => 'infraestructura', 'delta' => 1,
        ])->assertOk()->assertJsonPath('acc.infraestructura', 1);
    }

    public function test_regular_user_cannot_validate_preconditions(): void
    {
        $target = $this->user()->sumateParticipant;

        $this->actingAs($this->user())
            ->patchJson("/api/sumate/participants/{$target->id}/precondiciones", [
                'pre' => ['disciplinarios' => true],
            ])
            ->assertForbidden();
    }
}
