<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PUT /api/users/{user}/roles exige perm:usuarios,editar, como el resto de
 * /api/users/* de escritura (ver routes/api.php).
 */
class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_put_roles_syncs_the_users_role_set(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $roleA = Role::factory()->create();
        $roleB = Role::factory()->create();

        $res = $this->actingAs($admin)->putJson("/api/users/{$user->id}/roles", [
            'roleSlugs' => [$roleA->slug, $roleB->slug],
        ])->assertOk();

        $this->assertEqualsCanonicalizing([$roleA->slug, $roleB->slug], collect($res->json('roles'))->pluck('slug')->all());
        $this->assertEqualsCanonicalizing([$roleA->slug, $roleB->slug], $user->fresh()->roles->pluck('slug')->all());

        // Reasignar reemplaza, no acumula.
        $roleC = Role::factory()->create();
        $this->actingAs($admin)->putJson("/api/users/{$user->id}/roles", ['roleSlugs' => [$roleC->slug]])->assertOk();
        $this->assertSame([$roleC->slug], $user->fresh()->roles->pluck('slug')->all());
    }

    public function test_an_empty_role_slugs_array_is_accepted_and_clears_the_users_roles(): void
    {
        // Regresión: Laravel trata un array vacío como "ausente" bajo la regla
        // 'required'; un usuario SÍ puede legítimamente quedar sin roles propios
        // (solo Cualquiera). La regla correcta es 'present'.
        $admin = $this->admin();
        $user = User::factory()->withRoles(Role::factory()->create()->slug)->create();

        $this->actingAs($admin)->putJson("/api/users/{$user->id}/roles", ['roleSlugs' => []])
            ->assertOk()
            ->assertJsonPath('roles', []);

        $this->assertSame([], $user->fresh()->roles->pluck('slug')->all());
    }

    public function test_requires_admin_access(): void
    {
        $plain = User::factory()->create();
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $this->actingAs($plain)->putJson("/api/users/{$user->id}/roles", ['roleSlugs' => [$role->slug]])
            ->assertForbidden();
    }

    public function test_user_admin_resource_exposes_roles(): void
    {
        $admin = $this->admin();
        $role = Role::factory()->create(['name' => 'Coordinador SIG']);
        $user = User::factory()->withRoles($role->slug)->create();

        $this->actingAs($admin)->getJson("/api/users?q={$user->email}")
            ->assertOk()
            ->assertJsonPath('data.0.roles.0.slug', $role->slug)
            ->assertJsonPath('data.0.roles.0.name', 'Coordinador SIG');
    }

    public function test_list_filters_by_role_slug(): void
    {
        $admin = $this->admin();
        $role = Role::factory()->create();
        $matching = User::factory()->withRoles($role->slug)->create();
        User::factory()->create(); // no debe aparecer

        $res = $this->actingAs($admin)->getJson("/api/users?roleSlug={$role->slug}")->assertOk();

        $this->assertSame([$matching->id], collect($res->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_creating_a_user_with_role_slugs_attaches_them(): void
    {
        $admin = $this->admin();
        $role = Role::factory()->create();

        $res = $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'Nuevo Colaborador',
            'email' => 'nuevo.colaborador@insumma.co',
            'password' => 'Insumma2026!',
            'roleType' => 'user',
            'roleSlugs' => [$role->slug],
        ])->assertCreated();

        $this->assertSame([$role->slug], User::find($res->json('id'))->roles->pluck('slug')->all());
    }
}
