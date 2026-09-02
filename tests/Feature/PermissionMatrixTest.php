<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->superadmin()->create();
    }

    public function test_catalog_returns_exactly_twelve_views_and_four_actions(): void
    {
        $res = $this->actingAs($this->superadmin())->getJson('/api/permissions/catalog')->assertOk();

        $this->assertCount(12, $res->json('items'));
        $this->assertCount(4, $res->json('actions'));
        $this->assertEqualsCanonicalizing(Permissions::views(), collect($res->json('items'))->pluck('id')->all());
    }

    public function test_matrix_get_shape(): void
    {
        $res = $this->actingAs($this->superadmin())->getJson('/api/permissions/matrix')->assertOk();

        $res->assertJsonStructure(['views', 'actions', 'roles' => [['id', 'slug', 'name', 'protected', 'isDefault', 'permissions']]]);
        // Los 9 roles sembrados por la migración de datos.
        $this->assertCount(9, $res->json('roles'));
    }

    public function test_saving_an_empty_permissions_map_is_accepted_not_rejected_as_missing(): void
    {
        // Regresión: Laravel trata un array vacío como "ausente" bajo la regla
        // 'required'; un rol SÍ puede legítimamente quedar sin ningún permiso
        // (p. ej. al revocar el último). La regla correcta es 'present'.
        $role = Role::factory()->create();
        $role->syncMatrix(['sst' => ['ver']]);

        $this->actingAs($this->superadmin())->putJson('/api/permissions/matrix', [
            'roles' => [['id' => $role->id, 'permissions' => []]],
        ])->assertOk();

        $this->assertSame([], $role->fresh()->matrix());
    }

    public function test_put_saves_several_roles_in_one_call(): void
    {
        $roleA = Role::factory()->create();
        $roleB = Role::factory()->create();

        $this->actingAs($this->superadmin())->putJson('/api/permissions/matrix', [
            'roles' => [
                ['id' => $roleA->id, 'permissions' => ['sst' => ['ver', 'crear']]],
                ['id' => $roleB->id, 'permissions' => ['sig' => ['ver']]],
            ],
        ])->assertOk();

        $this->assertSame(['sst' => ['crear', 'ver']], $roleA->fresh()->matrix());
        $this->assertSame(['sig' => ['ver']], $roleB->fresh()->matrix());
    }

    public function test_unknown_view_is_rejected(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->superadmin())->putJson('/api/permissions/matrix', [
            'roles' => [['id' => $role->id, 'permissions' => ['no-existe' => ['ver']]]],
        ])->assertStatus(422);
    }

    public function test_unknown_action_is_rejected(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->superadmin())->putJson('/api/permissions/matrix', [
            'roles' => [['id' => $role->id, 'permissions' => ['sst' => ['volar']]]],
        ])->assertStatus(422);
    }

    public function test_a_row_without_ver_drops_the_other_actions(): void
    {
        $role = Role::factory()->create();
        $role->syncMatrix(['sst' => ['crear', 'editar', 'eliminar']]); // sin 'ver'

        $this->assertSame([], $role->fresh()->matrix());
    }

    public function test_superadmin_matrix_cannot_be_edited(): void
    {
        $superadminRole = Role::where('slug', Role::SUPERADMIN)->firstOrFail();

        $this->actingAs($this->superadmin())->putJson('/api/permissions/matrix', [
            'roles' => [['id' => $superadminRole->id, 'permissions' => ['sst' => ['ver']]]],
        ])->assertStatus(422)
            ->assertJsonPath('message', 'El rol Superadministrador siempre tiene todos los permisos.');

        $this->actingAs($this->superadmin())
            ->putJson("/api/roles/{$superadminRole->id}/permissions", ['permissions' => ['sst' => ['ver']]])
            ->assertStatus(422);
    }
}
