<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleBuilderTest extends TestCase
{
    use RefreshDatabase;

    // superadmin, no un rol real: estos tests cubren la mecánica de módulos
    // (slug/sección, tipos, reorder), no los límites de permisos por sección
    // (eso ya lo cubre PermissionEnforcementTest) — y ningún rol sembrado tiene
    // CRUD completo en rh+sst+sig a la vez.
    private function admin(): User
    {
        return User::factory()->superadmin()->create();
    }

    public function test_index_only_returns_visible_modules(): void
    {
        $user = User::factory()->create();
        Module::factory()->create(['section' => 'rh', 'visible' => true, 'position' => 0]);
        Module::factory()->create(['section' => 'rh', 'visible' => false, 'position' => 1]);

        $res = $this->actingAs($user)->getJson('/api/rh/modules')->assertOk();

        $this->assertCount(1, $res->json('items'));
    }

    public function test_all_includes_hidden_modules_and_requires_editar(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        Module::factory()->create(['section' => 'rh', 'visible' => false]);

        $this->actingAs($user)->getJson('/api/rh/modules/all')->assertForbidden();

        $res = $this->actingAs($admin)->getJson('/api/rh/modules/all')->assertOk();
        $this->assertCount(1, $res->json('items'));
    }

    public function test_update_and_delete_work_by_slug_not_by_numeric_id(): void
    {
        // Bug real: la ruta ligaba {module} por id pero el frontend envía el slug
        // (ModuleResource expone id=slug); PUT/DELETE devolvían 404 siempre.
        $admin = $this->admin();
        $module = Module::factory()->create(['section' => 'sst', 'slug' => 'epp', 'label' => 'EPP']);

        $this->actingAs($admin)->putJson('/api/sst/modules/epp', ['label' => 'EPP actualizado'])
            ->assertOk()
            ->assertJsonPath('label', 'EPP actualizado');

        $this->actingAs($admin)->deleteJson('/api/sst/modules/epp')->assertOk();
        $this->assertDatabaseMissing('modules', ['id' => $module->id]);
    }

    public function test_duplicate_slug_in_the_same_section_is_a_422_not_a_500(): void
    {
        $admin = $this->admin();
        Module::factory()->create(['section' => 'sig', 'slug' => 'calidad']);

        $this->actingAs($admin)->postJson('/api/sig/modules', [
            'slug' => 'calidad', 'label' => 'Otra', 'icon' => '📄', 'color' => '#000', 'bg' => '#fff', 'desc' => 'Descripción larga',
        ])->assertStatus(422)->assertJsonValidationErrors('slug');
    }

    public function test_the_same_slug_is_allowed_in_a_different_section(): void
    {
        $admin = $this->admin();
        Module::factory()->create(['section' => 'sig', 'slug' => 'sst']);

        $this->actingAs($admin)->postJson('/api/sst/modules', [
            'slug' => 'sst', 'label' => 'SST', 'icon' => '📄', 'color' => '#000', 'bg' => '#fff', 'desc' => 'Descripción larga',
            'href' => 'https://insumma.co',
        ])->assertCreated();
    }

    public function test_reorder_updates_positions_scoped_to_section(): void
    {
        $admin = $this->admin();
        $a = Module::factory()->create(['section' => 'rh', 'slug' => 'a', 'position' => 0]);
        $b = Module::factory()->create(['section' => 'rh', 'slug' => 'b', 'position' => 1]);

        $this->actingAs($admin)->patchJson('/api/rh/modules/reorder', ['ids' => ['b', 'a']])->assertOk();

        $this->assertSame(0, $b->fresh()->position);
        $this->assertSame(1, $a->fresh()->position);
    }

    public function test_type_specific_validation_rejects_incomplete_config(): void
    {
        $admin = $this->admin();
        $base = ['slug' => 'nuevo', 'label' => 'Nuevo', 'icon' => '📄', 'color' => '#000', 'bg' => '#fff', 'desc' => 'Descripción larga'];

        $this->actingAs($admin)->postJson('/api/rh/modules', $base + ['type' => 'enlace'])
            ->assertStatus(422)->assertJsonValidationErrors('href');

        $this->actingAs($admin)->postJson('/api/rh/modules', $base + ['type' => 'formulario'])
            ->assertStatus(422)->assertJsonValidationErrors('config');

        $this->actingAs($admin)->postJson('/api/rh/modules', $base + ['type' => 'calendario'])
            ->assertStatus(422)->assertJsonValidationErrors('config');

        $this->actingAs($admin)->postJson('/api/rh/modules', $base + ['type' => 'enlace', 'href' => 'https://insumma.co'])
            ->assertCreated();
    }

    public function test_sintyc_and_inicio_sections_are_reachable(): void
    {
        $admin = $this->admin();
        Module::factory()->create(['section' => 'sintyc']);
        Module::factory()->create(['section' => 'inicio']);

        $this->actingAs($admin)->getJson('/api/sintyc/modules')->assertOk();
        $this->actingAs($admin)->getJson('/api/inicio/modules')->assertOk();
    }
}
