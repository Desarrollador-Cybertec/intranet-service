<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\SumateAccion;
use App\Models\SumateRequest;
use App\Models\User;
use App\Services\SumateService;
use Database\Seeders\CourseSeeder;
use Database\Seeders\SumateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SumateRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // SumateSeeder exige que estos dos ya existan (ver su propio guard); no se usan
        // directamente en este test, son solo para satisfacer esa dependencia interna.
        User::factory()->create(['email' => 'user@cybertec.com.co', 'joined_at' => now()->subYears(2)]);
        User::factory()->create(['email' => 'admin@cybertec.com.co', 'joined_at' => now()->subYears(2)]);

        // Fixture propio de este test: dos usuarios elegibles (uno sin acciones aún,
        // otro con yoAporto ya al tope de su propia acción) y uno nunca elegible.
        $eligible = User::factory()->create(['email' => 'reporta@insumma.co', 'name' => 'Reporta', 'joined_at' => now()->subYears(2)]);
        $maxedOut = User::factory()->create(['email' => 'maxeado@insumma.co', 'name' => 'Maxeado', 'joined_at' => now()->subYears(2)]);
        $noEligible = User::factory()->create(['email' => 'noelegible@insumma.co', 'name' => 'No Elegible', 'joined_at' => now()]);

        $this->seed(CourseSeeder::class);
        $this->seed(SumateSeeder::class);

        foreach ([$eligible, $maxedOut] as $u) {
            foreach (Course::where('tag', 'Obligatorio')->pluck('id') as $courseId) {
                $u->enrollments()->updateOrCreate(['course_id' => $courseId], ['completed' => true]);
            }
        }

        $sumate = app(SumateService::class);

        $p1 = $sumate->syncParticipantFor($eligible);
        $sumate->setPreconditions($p1, ['puntualidad' => true, 'asistencia' => true, 'disciplinarios' => true]);

        $p2 = $sumate->syncParticipantFor($maxedOut);
        $sumate->setPreconditions($p2, ['puntualidad' => true, 'asistencia' => true, 'disciplinarios' => true]);
        $yoAporto = SumateAccion::where('slug', 'yoAporto')->firstOrFail();
        $p2->actionCounts()->create(['accion_id' => $yoAporto->id, 'count' => $yoAporto->max]);

        // joined_at = hoy → antigüedad automática falla → nunca elegible, sin necesidad
        // de tocar las manuales.
        $sumate->syncParticipantFor($noEligible);
    }

    private function reporter(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    // sigsst trae sumate.editar completo; rrhh solo sumate.ver (línea base de "Cualquiera").
    private function approver(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'sig-sst')->firstOrFail());

        return $user;
    }

    private function baselineOnly(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'rrhh')->firstOrFail());

        return $user;
    }

    public function test_a_user_without_a_participant_cannot_report_an_action(): void
    {
        $user = User::factory()->create(); // sin syncParticipantFor

        $this->actingAs($user)->postJson('/api/sumate/solicitudes', [
            'accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.',
        ])->assertStatus(422);
    }

    public function test_reporting_an_action_creates_a_pending_request(): void
    {
        $reporter = $this->reporter('reporta@insumma.co');

        $res = $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', [
            'accionId' => 'yoAporto', 'description' => 'Propuse mejorar el proceso de recepción de materia prima.',
        ])->assertCreated();

        $this->assertSame('pendiente', $res->json('status'));
        $this->assertSame('yoAporto', $res->json('accion.id'));
    }

    public function test_a_second_pending_request_for_the_same_action_is_rejected(): void
    {
        $reporter = $this->reporter('reporta@insumma.co');
        $payload = ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'];

        $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', $payload)->assertCreated();
        $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', $payload)->assertStatus(422);
    }

    public function test_a_new_request_for_the_same_action_is_allowed_once_the_previous_one_is_resolved(): void
    {
        $reporter = $this->reporter('reporta@insumma.co');
        $approver = $this->approver();
        $payload = ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'];

        $first = $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', $payload)->assertCreated();
        $this->actingAs($approver)->postJson("/api/sumate/solicitudes/{$first->json('id')}/aprobar")->assertOk();

        $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', $payload)->assertCreated();
    }

    public function test_mine_lists_only_the_current_users_own_requests(): void
    {
        $reporter = $this->reporter('reporta@insumma.co');
        $other = $this->reporter('maxeado@insumma.co');
        $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'])->assertCreated();
        $this->actingAs($other)->postJson('/api/sumate/solicitudes', ['accionId' => 'redes', 'description' => 'Una descripción con largo suficiente.'])->assertCreated();

        $res = $this->actingAs($reporter)->getJson('/api/sumate/solicitudes/mias')->assertOk();

        $this->assertCount(1, $res->json('items'));
        $this->assertSame('yoAporto', $res->json('items.0.accion.id'));
    }

    public function test_a_baseline_sumate_ver_user_cannot_list_or_approve_others_requests(): void
    {
        // "Cualquiera" ya trae sumate.ver de línea base — la bandeja de aprobación
        // (con reportes ajenos) exige sumate.editar, no basta con .ver.
        $reporter = $this->reporter('reporta@insumma.co');
        $bystander = $this->baselineOnly();
        $created = $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'])->assertCreated();

        $this->actingAs($bystander)->getJson('/api/sumate/solicitudes')->assertForbidden();
        $this->actingAs($bystander)->postJson("/api/sumate/solicitudes/{$created->json('id')}/aprobar")->assertForbidden();
    }

    public function test_approving_grants_points_and_stamps_the_reviewer(): void
    {
        $reporter = $this->reporter('reporta@insumma.co');
        $approver = $this->approver();
        $created = $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'])->assertCreated();

        $res = $this->actingAs($approver)->postJson("/api/sumate/solicitudes/{$created->json('id')}/aprobar")->assertOk();

        $this->assertSame('aprobada', $res->json('request.status'));
        $this->assertSame(10, $res->json('request.pointsGranted'));
        $this->assertSame($approver->id, $res->json('request.reviewedBy.id'));
        $this->assertNotNull($res->json('request.reviewedAt'));
        $this->assertSame(10, $res->json('participant.pts'));

        $sumateRequest = SumateRequest::findOrFail($created->json('id'));
        $this->assertTrue($sumateRequest->granted);
        $this->assertNotNull($sumateRequest->granted_at);
    }

    public function test_approving_a_second_time_is_a_409_and_does_not_duplicate_points(): void
    {
        $reporter = $this->reporter('reporta@insumma.co');
        $approver = $this->approver();
        $created = $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'])->assertCreated();

        $this->actingAs($approver)->postJson("/api/sumate/solicitudes/{$created->json('id')}/aprobar")->assertOk();
        $this->actingAs($approver)->postJson("/api/sumate/solicitudes/{$created->json('id')}/aprobar")->assertStatus(409);

        $participant = app(SumateService::class)->summary($this->reporter('reporta@insumma.co')->sumateParticipant);
        $this->assertSame(10, $participant['pts']);
    }

    public function test_approving_above_the_actions_own_cap_grants_zero_points_but_still_approves(): void
    {
        $reporter = $this->reporter('maxeado@insumma.co'); // yoAporto ya al máximo (3/3)
        $approver = $this->approver();
        $created = $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'])->assertCreated();

        $res = $this->actingAs($approver)->postJson("/api/sumate/solicitudes/{$created->json('id')}/aprobar")->assertOk();

        $this->assertSame('aprobada', $res->json('request.status'));
        $this->assertSame(0, $res->json('request.pointsGranted'));
    }

    public function test_approving_a_request_from_a_non_eligible_participant_is_a_422_and_leaves_it_pending(): void
    {
        $reporter = $this->reporter('noelegible@insumma.co');
        $approver = $this->approver();
        $created = $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'])->assertCreated();

        $this->actingAs($approver)->postJson("/api/sumate/solicitudes/{$created->json('id')}/aprobar")->assertStatus(422);

        $this->assertSame('pendiente', SumateRequest::findOrFail($created->json('id'))->status);
    }

    public function test_rejecting_without_a_reason_is_a_422(): void
    {
        $reporter = $this->reporter('reporta@insumma.co');
        $approver = $this->approver();
        $created = $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'])->assertCreated();

        $this->actingAs($approver)->postJson("/api/sumate/solicitudes/{$created->json('id')}/rechazar", [])
            ->assertStatus(422)->assertJsonValidationErrors('reason');
    }

    public function test_rejecting_with_a_reason_marks_it_rejected_and_grants_nothing(): void
    {
        $reporter = $this->reporter('reporta@insumma.co');
        $approver = $this->approver();
        $created = $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'])->assertCreated();

        $res = $this->actingAs($approver)->postJson("/api/sumate/solicitudes/{$created->json('id')}/rechazar", [
            'reason' => 'La descripción no corresponde a un aporte real.',
        ])->assertOk();

        $this->assertSame('rechazada', $res->json('status'));
        $this->assertSame('La descripción no corresponde a un aporte real.', $res->json('rejectionReason'));

        $participant = app(SumateService::class)->summary($this->reporter('reporta@insumma.co')->sumateParticipant);
        $this->assertSame(0, $participant['pts']);
    }

    public function test_rejecting_a_second_time_is_a_409(): void
    {
        $reporter = $this->reporter('reporta@insumma.co');
        $approver = $this->approver();
        $created = $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'])->assertCreated();

        $this->actingAs($approver)->postJson("/api/sumate/solicitudes/{$created->json('id')}/rechazar", ['reason' => 'No corresponde.'])->assertOk();
        $this->actingAs($approver)->postJson("/api/sumate/solicitudes/{$created->json('id')}/rechazar", ['reason' => 'No corresponde de nuevo.'])->assertStatus(409);
    }

    public function test_the_admin_inbox_exposes_eligibility_and_remaining_capacity_before_approving(): void
    {
        $reporter = $this->reporter('reporta@insumma.co');
        $approver = $this->approver();
        $this->actingAs($reporter)->postJson('/api/sumate/solicitudes', ['accionId' => 'yoAporto', 'description' => 'Una descripción con largo suficiente.'])->assertCreated();

        $res = $this->actingAs($approver)->getJson('/api/sumate/solicitudes?status=pendiente')->assertOk();

        $this->assertTrue($res->json('items.0.participantEligible'));
        $this->assertSame(100, $res->json('items.0.participantRemaining'));
    }
}
