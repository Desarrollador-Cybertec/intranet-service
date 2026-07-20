<?php

namespace Tests\Feature;

use App\Models\SumateParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los usuarios importados de la nómina llegan sin cargo, área, teléfono ni fecha
 * de ingreso: el backend los bloquea con 428 hasta que completan su perfil.
 */
class ProfileOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function imported(): User
    {
        return User::factory()->imported()->create(['name' => 'Camila Altamar']);
    }

    private function completePayload(): array
    {
        return [
            'name' => 'Camila Altamar Ruiz',
            'role' => 'Auxiliar de Equipos',
            'area' => 'Logística',
            'phone' => '3001234567',
            'joinedAt' => '2024-03-01',
            'extension' => '210',
        ];
    }

    public function test_imported_user_is_blocked_with_428(): void
    {
        $res = $this->actingAs($this->imported())->getJson('/api/news');

        $res->assertStatus(428)
            ->assertJsonPath('missingFields', ['role', 'area', 'phone', 'joinedAt']);
    }

    public function test_imported_user_can_still_login_and_read_own_profile(): void
    {
        $res = $this->actingAs($this->imported())->getJson('/api/auth/me')->assertOk();

        $res->assertJsonPath('profileCompleted', false)
            ->assertJsonPath('missingFields', ['role', 'area', 'phone', 'joinedAt']);
    }

    public function test_login_response_reports_profile_completed(): void
    {
        $user = $this->imported();
        $user->forceFill(['password' => 'secret123'])->save();

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('user.profileCompleted', false);
    }

    public function test_completing_the_profile_unlocks_the_api(): void
    {
        $user = $this->imported();

        $this->actingAs($user)->patchJson('/api/auth/me', $this->completePayload())
            ->assertOk()
            ->assertJsonPath('profileCompleted', true)
            ->assertJsonPath('missingFields', [])
            ->assertJsonPath('role', 'Auxiliar de Equipos')
            ->assertJsonPath('joinedAt', '2024-03-01');

        $user->refresh();
        $this->assertNotNull($user->profile_completed_at);
        $this->assertSame('CA', $user->initials); // recalculadas desde el nombre

        $this->actingAs($user)->getJson('/api/news')->assertOk();
    }

    public function test_partial_update_keeps_the_user_blocked(): void
    {
        $user = $this->imported();

        $this->actingAs($user)->patchJson('/api/auth/me', ['role' => 'Auxiliar de Equipos'])
            ->assertOk()
            ->assertJsonPath('profileCompleted', false)
            ->assertJsonPath('missingFields', ['area', 'phone', 'joinedAt']);

        $this->actingAs($user)->getJson('/api/news')->assertStatus(428);
    }

    public function test_completing_the_profile_syncs_the_sumate_participant(): void
    {
        $user = $this->imported();

        $this->actingAs($user)->patchJson('/api/auth/me', $this->completePayload())->assertOk();

        $participant = SumateParticipant::where('user_id', $user->id)->first();

        $this->assertNotNull($participant);
        $this->assertSame('Camila Altamar Ruiz', $participant->name);
        $this->assertSame('Logística', $participant->area);
    }

    public function test_user_with_complete_profile_is_never_blocked(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/news')->assertOk();
    }
}
