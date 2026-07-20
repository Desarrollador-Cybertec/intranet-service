<?php

namespace Tests\Feature;

use App\Models\SumateParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ── Permisos ─────────────────────────────────────────────────────────────

    public function test_a_regular_user_cannot_reach_the_administration(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->getJson('/api/users')->assertForbidden();
        $this->actingAs($user)->postJson('/api/users', [])->assertForbidden();
        $this->actingAs($user)->patchJson("/api/users/{$other->id}", [])->assertForbidden();
        $this->actingAs($user)->postJson("/api/users/{$other->id}/password")->assertForbidden();
    }

    // ── Listado y filtros ────────────────────────────────────────────────────

    public function test_lists_users_paginated(): void
    {
        $admin = $this->admin();
        User::factory()->count(3)->create();

        $this->actingAs($admin)->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'roleType', 'active', 'profileCompleted']], 'meta']);
    }

    public function test_filters_by_search_role_and_status(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Camila Altamar', 'email' => 'camila@insumma.co']);
        User::factory()->inactive()->create(['name' => 'Inactivo Uno']);
        User::factory()->imported()->create(['name' => 'Sin Perfil']);

        $byName = $this->actingAs($admin)->getJson('/api/users?q=Camila')->assertOk();
        $this->assertSame(['Camila Altamar'], collect($byName->json('data'))->pluck('name')->all());

        $byEmail = $this->actingAs($admin)->getJson('/api/users?q=camila@insumma')->assertOk();
        $this->assertCount(1, $byEmail->json('data'));

        $admins = $this->actingAs($admin)->getJson('/api/users?roleType=admin')->assertOk();
        $this->assertCount(1, $admins->json('data'));

        $inactive = $this->actingAs($admin)->getJson('/api/users?status=inactive')->assertOk();
        $this->assertSame(['Inactivo Uno'], collect($inactive->json('data'))->pluck('name')->all());

        $incomplete = $this->actingAs($admin)->getJson('/api/users?status=incomplete')->assertOk();
        $this->assertSame(['Sin Perfil'], collect($incomplete->json('data'))->pluck('name')->all());
    }

    // ── Alta ─────────────────────────────────────────────────────────────────

    public function test_creates_a_user_with_derived_initials_and_a_sumate_participant(): void
    {
        $res = $this->actingAs($this->admin())->postJson('/api/users', [
            'name' => 'Camila Altamar',
            'email' => 'Camila.Altamar@insumma.co',
            'password' => 'Insumma2026!',
            'roleType' => 'user',
        ])->assertCreated();

        $res->assertJsonPath('initials', 'CA')
            ->assertJsonPath('email', 'camila.altamar@insumma.co') // normalizado
            ->assertJsonPath('active', true)
            ->assertJsonPath('profileCompleted', false); // sin cargo/área/teléfono/ingreso

        $user = User::where('email', 'camila.altamar@insumma.co')->first();
        $this->assertTrue(Hash::check('Insumma2026!', $user->password));
        $this->assertNotNull(SumateParticipant::where('user_id', $user->id)->first());
    }

    public function test_creating_with_a_full_profile_skips_the_onboarding(): void
    {
        $this->actingAs($this->admin())->postJson('/api/users', [
            'name' => 'Camila Altamar',
            'email' => 'camila@insumma.co',
            'password' => 'Insumma2026!',
            'roleType' => 'user',
            'role' => 'Auxiliar de Equipos',
            'area' => 'Logística',
            'phone' => '3001234567',
            'joinedAt' => '2024-03-01',
        ])->assertCreated()->assertJsonPath('profileCompleted', true);
    }

    public function test_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'camila@insumma.co']);

        $this->actingAs($this->admin())->postJson('/api/users', [
            'name' => 'Otra Camila',
            'email' => 'camila@insumma.co',
            'password' => 'Insumma2026!',
            'roleType' => 'user',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    // ── Edición ──────────────────────────────────────────────────────────────

    public function test_updates_the_profile_and_recomputes_initials(): void
    {
        $user = User::factory()->create(['name' => 'Camila Altamar']);

        $this->actingAs($this->admin())->patchJson("/api/users/{$user->id}", [
            'name' => 'Rocío Bermúdez',
            'area' => 'Gestión Humana',
        ])->assertOk()->assertJsonPath('initials', 'RB');

        $this->assertSame('Gestión Humana', $user->fresh()->area);
    }

    public function test_completing_the_profile_from_the_admin_lifts_the_block(): void
    {
        $user = User::factory()->imported()->create();

        $this->actingAs($this->admin())->patchJson("/api/users/{$user->id}", [
            'role' => 'Auxiliar', 'area' => 'Logística', 'phone' => '3001234567', 'joinedAt' => '2024-03-01',
        ])->assertOk()->assertJsonPath('profileCompleted', true);

        $this->actingAs($user->fresh())->getJson('/api/news')->assertOk();
    }

    public function test_promotes_a_user_to_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())->patchJson("/api/users/{$user->id}", ['roleType' => 'admin'])
            ->assertOk()->assertJsonPath('roleType', 'admin');

        // Y ahora puede administrar.
        $this->actingAs($user->fresh())->getJson('/api/users')->assertOk();
    }

    // ── Guardas ──────────────────────────────────────────────────────────────

    public function test_an_admin_cannot_change_their_own_role(): void
    {
        $admin = $this->admin();
        User::factory()->admin()->create(); // otro admin: no es el caso del "último"

        $this->actingAs($admin)->patchJson("/api/users/{$admin->id}", ['roleType' => 'user'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No puedes cambiar tu propio rol. Pídeselo a otro administrador.');

        $this->assertSame('admin', $admin->fresh()->role_type);
    }

    public function test_an_admin_cannot_deactivate_themselves(): void
    {
        $admin = $this->admin();
        User::factory()->admin()->create();

        $this->actingAs($admin)->patchJson("/api/users/{$admin->id}", ['active' => false])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No puedes desactivar tu propia cuenta.');

        $this->assertTrue($admin->fresh()->active);
    }

    public function test_the_last_active_admin_cannot_be_demoted_or_deactivated(): void
    {
        $admin = $this->admin();
        $other = User::factory()->admin()->create();

        // Con `other` desactivado, `admin` queda como único administrador activo.
        $this->actingAs($admin)->patchJson("/api/users/{$other->id}", ['active' => false])->assertOk();

        // Un tercer admin intenta degradar al último activo.
        $rescuer = User::factory()->admin()->create();
        $this->actingAs($rescuer)->patchJson("/api/users/{$admin->id}", ['roleType' => 'user'])->assertOk();

        // Ahora `rescuer` es el último: no puede degradarse ni ser desactivado.
        $another = User::factory()->create();
        $this->actingAs($rescuer)->patchJson("/api/users/{$another->id}", ['roleType' => 'admin'])->assertOk();
        $this->actingAs($another->fresh())->patchJson("/api/users/{$rescuer->id}", ['active' => false])->assertOk();

        // Queda `another` como único admin activo: desactivarlo debe fallar.
        $this->actingAs($another->fresh())->patchJson("/api/users/{$another->id}", ['active' => false])
            ->assertStatus(422);
    }

    // ── Estado de la cuenta ──────────────────────────────────────────────────

    public function test_deactivating_revokes_tokens_and_blocks_login(): void
    {
        $user = User::factory()->create(['email' => 'camila@insumma.co', 'password' => 'Insumma2026!']);
        $user->createToken('intranet');
        $this->assertSame(1, $user->tokens()->count());

        $this->actingAs($this->admin())->patchJson("/api/users/{$user->id}", ['active' => false])
            ->assertOk()->assertJsonPath('active', false);

        $this->assertSame(0, $user->fresh()->tokens()->count());

        $this->postJson('/api/auth/login', ['email' => 'camila@insumma.co', 'password' => 'Insumma2026!'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tu cuenta está desactivada. Comunícate con Gestión Humana.');
    }

    public function test_an_inactive_account_with_a_live_token_is_cut_off(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->getJson('/api/news')->assertForbidden();
        $this->actingAs($user)->getJson('/api/auth/me')->assertForbidden();
    }

    public function test_reactivating_restores_access(): void
    {
        $user = User::factory()->inactive()->create(['email' => 'camila@insumma.co', 'password' => 'Insumma2026!']);

        $this->actingAs($this->admin())->patchJson("/api/users/{$user->id}", ['active' => true])
            ->assertOk()->assertJsonPath('active', true);

        $this->postJson('/api/auth/login', ['email' => 'camila@insumma.co', 'password' => 'Insumma2026!'])
            ->assertOk();
    }

    // ── Contraseña ───────────────────────────────────────────────────────────

    public function test_resets_the_password_and_returns_it_once(): void
    {
        $user = User::factory()->create(['email' => 'camila@insumma.co']);
        $user->createToken('intranet');

        $res = $this->actingAs($this->admin())->postJson("/api/users/{$user->id}/password")->assertOk();

        $password = $res->json('password');
        $this->assertIsString($password);
        $this->assertGreaterThanOrEqual(12, strlen($password));
        $this->assertTrue(Hash::check($password, $user->fresh()->password));

        // La sesión anterior se cierra.
        $this->assertSame(0, $user->fresh()->tokens()->count());

        $this->postJson('/api/auth/login', ['email' => 'camila@insumma.co', 'password' => $password])
            ->assertOk();
    }

    public function test_accepts_an_explicit_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())
            ->postJson("/api/users/{$user->id}/password", ['password' => 'ClaveNueva2026'])
            ->assertOk()
            ->assertJsonPath('password', 'ClaveNueva2026');

        $this->assertTrue(Hash::check('ClaveNueva2026', $user->fresh()->password));
    }
}
