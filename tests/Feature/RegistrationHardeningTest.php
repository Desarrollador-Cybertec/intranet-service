<?php

namespace Tests\Feature;

use App\Models\SumateParticipant;
use App\Models\User;
use App\Notifications\RegistrationPendingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationHardeningTest extends TestCase
{
    use RefreshDatabase;

    /** Un rol con usuarios.editar de verdad (asistente-gerencia no lo tiene). */
    private function admin(): User
    {
        return User::factory()->withRoles('rrhh')->create();
    }

    public function test_register_rejects_a_disallowed_email_domain(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Ana Gómez', 'email' => 'ana@gmail.com', 'password' => 'Secreta123', 'area' => 'Comercial',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_register_requires_at_least_8_characters(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Ana Gómez', 'email' => 'ana@insumma.co', 'password' => 'Corta12', 'area' => 'Comercial',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_register_normalizes_email_case_before_the_duplicate_check(): void
    {
        User::create(['name' => 'A', 'email' => 'juan@insumma.co', 'password' => 'secret123', 'role_type' => 'user']);

        $this->postJson('/api/auth/register', [
            'name' => 'B', 'email' => 'Juan@Insumma.co', 'password' => 'Secreta123', 'area' => 'Comercial',
        ])->assertStatus(409);
    }

    public function test_register_notifies_users_who_can_activate_accounts(): void
    {
        Notification::fake();
        $activator = User::factory()->withRoles('rrhh')->create();
        $cannotActivate = User::factory()->create(); // solo "cualquiera": no ve usuarios.editar

        $this->postJson('/api/auth/register', [
            'name' => 'Ana Gómez', 'email' => 'ana@insumma.co', 'password' => 'Secreta123', 'area' => 'Comercial',
        ])->assertStatus(202);

        Notification::assertSentTo($activator, RegistrationPendingNotification::class);
        Notification::assertNotSentTo($cannotActivate, RegistrationPendingNotification::class);
    }

    public function test_pending_account_cannot_login_and_gets_a_distinct_message(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Ana Gómez', 'email' => 'ana@insumma.co', 'password' => 'Secreta123', 'area' => 'Comercial',
        ])->assertStatus(202);

        $this->postJson('/api/auth/login', ['email' => 'ana@insumma.co', 'password' => 'Secreta123'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tu cuenta está pendiente de activación. Te avisaremos cuando puedas ingresar.');
    }

    public function test_admin_deactivated_account_gets_the_original_message(): void
    {
        $user = User::factory()->create(['active' => false, 'activated_at' => now()->subMonth(), 'password' => 'secret123']);

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tu cuenta está desactivada. Comunícate con Gestión Humana.');
    }

    public function test_activating_a_pending_account_stamps_activated_at_and_syncs_directory_and_sumate(): void
    {
        $admin = $this->admin();
        $pending = User::factory()->create(['active' => false, 'activated_at' => null]);

        $this->actingAs($admin)->patchJson("/api/users/{$pending->id}", ['active' => true])->assertOk();

        $pending->refresh();
        $this->assertTrue($pending->active);
        $this->assertNotNull($pending->activated_at);
        $this->assertDatabaseHas('directory_people', ['user_id' => $pending->id]);
        $this->assertSame(1, SumateParticipant::where('user_id', $pending->id)->count());
    }

    public function test_activating_does_not_overwrite_an_already_set_activated_at(): void
    {
        $admin = $this->admin();
        $original = now()->subYear();
        $user = User::factory()->create(['active' => false, 'activated_at' => $original]);

        $this->actingAs($admin)->patchJson("/api/users/{$user->id}", ['active' => true])->assertOk();

        // toDateTimeString() (segundos): MySQL redondea los microsegundos del Carbon en memoria.
        $this->assertSame($original->toDateTimeString(), $user->fresh()->activated_at->toDateTimeString());
    }

    public function test_pending_account_does_not_appear_in_directory_or_sumate(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Ana Gómez', 'email' => 'ana@insumma.co', 'password' => 'Secreta123', 'area' => 'Comercial',
        ])->assertStatus(202);

        $user = User::where('email', 'ana@insumma.co')->firstOrFail();
        $this->assertDatabaseMissing('directory_people', ['user_id' => $user->id]);
        $this->assertSame(0, SumateParticipant::where('user_id', $user->id)->count());
    }
}
