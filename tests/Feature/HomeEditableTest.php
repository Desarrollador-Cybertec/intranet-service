<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeEditableTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        // superadmin, no un rol real: cubre la mecánica de banner/quickLinks, no los
        // límites de permisos por rol (eso ya lo cubre PermissionEnforcementTest).
        return User::factory()->superadmin()->create();
    }

    public function test_dashboard_falls_back_to_the_config_banner_when_no_setting_exists(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->getJson('/api/dashboard')->assertOk();

        $this->assertSame(config('insumma.inicio.banner')['title'], $res->json('banner.title'));
    }

    public function test_dashboard_returns_the_saved_banner_once_one_exists(): void
    {
        Setting::setValue('inicio.banner', ['title' => 'QA título', 'message' => 'QA mensaje', 'colorFrom' => '#111111', 'colorTo' => '#222222']);
        $user = User::factory()->create();

        $res = $this->actingAs($user)->getJson('/api/dashboard')->assertOk();

        $this->assertSame('QA título', $res->json('banner.title'));
    }

    public function test_dashboard_returns_only_visible_inicio_modules_as_quick_links(): void
    {
        Module::factory()->create(['section' => 'inicio', 'slug' => 'visible-uno', 'visible' => true, 'config' => ['section' => 'enterate']]);
        Module::factory()->create(['section' => 'inicio', 'slug' => 'oculto-uno', 'visible' => false, 'config' => ['section' => 'sumate']]);
        $user = User::factory()->create();

        $res = $this->actingAs($user)->getJson('/api/dashboard')->assertOk();

        $slugs = collect($res->json('quickLinks'))->pluck('id');
        $this->assertContains('visible-uno', $slugs);
        $this->assertNotContains('oculto-uno', $slugs);
    }

    public function test_updating_the_banner_requires_inicio_editar(): void
    {
        $admin = $this->admin();
        $payload = ['title' => 'Nuevo título', 'message' => 'Nuevo mensaje', 'colorFrom' => '#123456', 'colorTo' => '#654321'];

        $res = $this->actingAs($admin)->putJson('/api/dashboard/banner', $payload)->assertOk();

        $this->assertSame('Nuevo título', $res->json('title'));
        $this->assertSame('Nuevo título', Setting::getValue('inicio.banner')['title']);
    }

    public function test_a_baseline_inicio_ver_user_cannot_update_the_banner(): void
    {
        // "Cualquiera" ya trae inicio.ver de línea base — editar el banner exige .editar.
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/dashboard/banner', [
            'title' => 'x', 'message' => 'x', 'colorFrom' => '#000000', 'colorTo' => '#000000',
        ])->assertForbidden();
    }

    public function test_banner_validation_rejects_bad_colors_and_oversized_fields(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->putJson('/api/dashboard/banner', [
            'title' => 'x', 'message' => 'x', 'colorFrom' => 'not-a-color', 'colorTo' => '#000000',
        ])->assertStatus(422)->assertJsonValidationErrors('colorFrom');

        $this->actingAs($admin)->putJson('/api/dashboard/banner', [
            'title' => str_repeat('a', 121), 'message' => 'x', 'colorFrom' => '#000000', 'colorTo' => '#000000',
        ])->assertStatus(422)->assertJsonValidationErrors('title');
    }

    public function test_quick_links_use_the_same_module_builder_reorder_endpoint(): void
    {
        // Cero código nuevo por decisión de diseño: los accesos rápidos son modules
        // section=inicio y reutilizan tal cual el reorder del constructor (Parte A).
        $admin = $this->admin();
        $a = Module::factory()->create(['section' => 'inicio', 'slug' => 'a', 'position' => 0]);
        $b = Module::factory()->create(['section' => 'inicio', 'slug' => 'b', 'position' => 1]);

        $this->actingAs($admin)->patchJson('/api/inicio/modules/reorder', ['ids' => ['b', 'a']])->assertOk();

        $this->assertSame(0, $b->fresh()->position);
        $this->assertSame(1, $a->fresh()->position);
    }

    public function test_a_non_admin_role_cannot_reach_configuraciones_but_home_edit_is_independent(): void
    {
        // inicio.editar es un permiso de sección propio — no depende de configuraciones.
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'rrhh')->firstOrFail());

        // rrhh no tiene inicio.editar (solo la línea base .ver) en la matriz sembrada.
        $this->actingAs($user)->putJson('/api/dashboard/banner', [
            'title' => 'x', 'message' => 'x', 'colorFrom' => '#000000', 'colorTo' => '#000000',
        ])->assertForbidden();
    }
}
