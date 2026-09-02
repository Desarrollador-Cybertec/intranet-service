<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->superadmin()->create();
    }

    public function test_lists_roles_ordered_by_position_with_users_count(): void
    {
        $admin = $this->superadmin();

        $res = $this->actingAs($admin)->getJson('/api/roles')->assertOk();

        $positions = collect($res->json('items'))->pluck('position');
        $this->assertSame($positions->sort()->values()->all(), $positions->all());

        $superadminRow = collect($res->json('items'))->firstWhere('slug', Role::SUPERADMIN);
        $this->assertSame(1, $superadminRow['usersCount']); // solo $admin
    }

    public function test_create_derives_slug_and_dedupes_collisions(): void
    {
        $admin = $this->superadmin();

        $first = $this->actingAs($admin)->postJson('/api/roles', ['name' => 'Auditoría Interna'])
            ->assertCreated();
        $this->assertSame('auditoria-interna', $first->json('slug'));

        $second = $this->actingAs($admin)->postJson('/api/roles', ['name' => 'Auditoría Interna'])
            ->assertCreated();
        $this->assertSame('auditoria-interna-2', $second->json('slug'));
    }

    public function test_create_with_initial_permissions(): void
    {
        $admin = $this->superadmin();

        $res = $this->actingAs($admin)->postJson('/api/roles', [
            'name' => 'Rol Nuevo',
            'permissions' => ['sig' => ['ver', 'editar']],
        ])->assertCreated();

        $role = Role::findOrFail($res->json('id'));
        $this->assertSame(['sig' => ['editar', 'ver']], $role->matrix());
    }

    public function test_rename_keeps_the_slug(): void
    {
        $admin = $this->superadmin();
        $role = Role::factory()->create(['slug' => 'coordinadores', 'name' => 'Coordinadores']);

        $this->actingAs($admin)->patchJson("/api/roles/{$role->id}", ['name' => 'Coordinadores de Área'])
            ->assertOk()
            ->assertJsonPath('slug', 'coordinadores')
            ->assertJsonPath('name', 'Coordinadores de Área');
    }

    public function test_delete_works(): void
    {
        $admin = $this->superadmin();
        $role = Role::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/roles/{$role->id}")->assertOk();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_protected_role_cannot_be_deleted_or_renamed(): void
    {
        $admin = $this->superadmin();
        $cualquiera = Role::where('slug', Role::DEFAULT)->firstOrFail();

        $this->actingAs($admin)->deleteJson("/api/roles/{$cualquiera->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Este rol es del sistema y no puede eliminarse.');

        $this->actingAs($admin)->patchJson("/api/roles/{$cualquiera->id}", ['name' => 'Otro nombre'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Los roles del sistema no se pueden renombrar.');
    }

    public function test_deleting_the_last_role_that_grants_configuraciones_editar_is_blocked(): void
    {
        // El único superadmin del sistema es $admin; borrar su rol lo dejaría sin nadie
        // que administre configuraciones.
        $admin = $this->superadmin();
        $superadminRole = Role::where('slug', Role::SUPERADMIN)->firstOrFail();

        // El rol superadmin es además `protected`, así que ya lo bloquea esa regla — probamos
        // el guardrail de huérfanos con un rol NO protegido que sea el único que lo concede.
        $onlyHolderRole = Role::factory()->create();
        $onlyHolderRole->syncMatrix(['configuraciones' => ['ver', 'editar', 'eliminar']]);
        $solo = User::factory()->withRoles($onlyHolderRole->slug)->create();

        // $admin sigue siendo superadmin (protegido, no se puede tocar), así que para forzar
        // el escenario de "único" hay que quitarle a $admin el rol superadmin primero.
        $admin->roles()->detach($superadminRole->id);

        $this->actingAs($solo)->deleteJson("/api/roles/{$onlyHolderRole->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Debe quedar al menos un usuario activo que pueda administrar roles y permisos.');
    }

    public function test_assigning_cualquiera_to_a_user_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->putJson("/api/users/{$user->id}/roles", ['roleSlugs' => [Role::DEFAULT]])
            ->assertStatus(422)
            ->assertJsonPath('message', 'El rol Cualquiera aplica a todos los usuarios automáticamente.');
    }

    public function test_a_user_without_configuraciones_permission_gets_403_on_every_endpoint(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $this->actingAs($user)->getJson('/api/roles')->assertForbidden();
        $this->actingAs($user)->postJson('/api/roles', ['name' => 'X'])->assertForbidden();
        $this->actingAs($user)->getJson("/api/roles/{$role->id}")->assertForbidden();
        $this->actingAs($user)->patchJson("/api/roles/{$role->id}", ['name' => 'Y'])->assertForbidden();
        $this->actingAs($user)->putJson("/api/roles/{$role->id}/permissions", ['permissions' => []])->assertForbidden();
        $this->actingAs($user)->deleteJson("/api/roles/{$role->id}")->assertForbidden();
        $this->actingAs($user)->getJson('/api/permissions/catalog')->assertForbidden();
        $this->actingAs($user)->getJson('/api/permissions/matrix')->assertForbidden();
        $this->actingAs($user)->putJson('/api/permissions/matrix', ['roles' => []])->assertForbidden();
    }
}
