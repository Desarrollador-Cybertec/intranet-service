<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El único bloque de rutas que ya usa `perm:` es Configuraciones (roles/permissions);
 * el resto de la API sigue en `role:admin` hasta que la matriz esté verificada desde
 * la propia UI (ver routes/api.php). Estos tests cubren el MECANISMO de `EnsurePermission`
 * + Gate::before, no la cobertura final de cada sección (eso llega cuando se reescriban
 * las rutas).
 */
class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_ver_allows_reading_but_not_writing(): void
    {
        $role = Role::factory()->create();
        $role->syncMatrix(['configuraciones' => ['ver']]);
        $user = User::factory()->withRoles($role->slug)->create();

        // GET /api/roles vive bajo usuarios.ver (lo consume la UI de Usuarios, no
        // Configuraciones): la prueba de lectura usa /api/permissions/catalog, que sí
        // sigue exclusivamente bajo configuraciones.
        $this->actingAs($user)->getJson('/api/permissions/catalog')->assertOk();
        $this->actingAs($user)->postJson('/api/roles', ['name' => 'Nuevo'])->assertForbidden();
    }

    public function test_ver_and_crear_allows_reading_and_creating_but_not_editing(): void
    {
        $role = Role::factory()->create();
        $role->syncMatrix(['configuraciones' => ['ver', 'crear']]);
        $user = User::factory()->withRoles($role->slug)->create();

        $this->actingAs($user)->getJson('/api/permissions/catalog')->assertOk();
        $this->actingAs($user)->postJson('/api/roles', ['name' => 'Nuevo Rol'])->assertCreated();

        $other = Role::factory()->create();
        $this->actingAs($user)->patchJson("/api/roles/{$other->id}", ['name' => 'Cambiado'])->assertForbidden();
    }

    public function test_without_ver_the_whole_view_is_forbidden_with_the_right_message(): void
    {
        $user = User::factory()->create(); // solo `cualquiera`, que no concede configuraciones

        $this->actingAs($user)->getJson('/api/permissions/catalog')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Esta sección no está habilitada para tu rol.');
    }

    public function test_permissions_from_two_roles_are_additive(): void
    {
        $roleA = Role::factory()->create();
        $roleA->syncMatrix(['sst' => ['ver', 'editar']]);

        $roleB = Role::factory()->create();
        $roleB->syncMatrix(['sig' => ['ver', 'crear']]);

        $user = User::factory()->withRoles($roleA->slug, $roleB->slug)->create();

        // Ninguno de los dos roles por separado cubre ambas vistas: la unión sí.
        $this->assertTrue($user->hasPermission('sst', 'editar'));
        $this->assertTrue($user->hasPermission('sig', 'crear'));
        $this->assertFalse($user->hasPermission('sig', 'editar'));
        $this->assertFalse($user->hasPermission('sst', 'crear'));
    }

    public function test_a_user_with_zero_explicit_roles_still_gets_cualquiera(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->hasPermission('inicio', 'ver'));
        $this->assertFalse($user->hasPermission('usuarios', 'ver'));
    }

    public function test_superadmin_bypasses_everything_via_gate_before(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        $this->actingAs($superadmin)->getJson('/api/roles')->assertOk();
        $this->actingAs($superadmin)->postJson('/api/roles', ['name' => 'Cualquier Cosa'])->assertCreated();
    }

    public function test_inactive_and_incomplete_profile_are_checked_before_the_permission(): void
    {
        $superadmin = User::factory()->superadmin()->inactive()->create();
        $this->actingAs($superadmin)->getJson('/api/roles')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tu cuenta está desactivada. Comunícate con Gestión Humana.');

        $importedSuperadmin = User::factory()->superadmin()->imported()->create();
        $this->actingAs($importedSuperadmin)->getJson('/api/roles')->assertStatus(428);
    }

    public function test_a_saved_matrix_change_is_visible_on_the_very_next_request(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->withRoles($role->slug)->create();

        $this->actingAs($user)->getJson('/api/permissions/catalog')->assertForbidden();

        $role->syncMatrix(['configuraciones' => ['ver']]);

        // Nueva petición == nueva instancia de User resuelta por el guard: sin caché que lo impida.
        $this->actingAs($user->fresh())->getJson('/api/permissions/catalog')->assertOk();
    }
}
