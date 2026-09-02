<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Module;
use App\Models\SumateParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_database_seeder_links_all_mock_data_to_real_users(): void
    {
        User::factory()->create(['email' => 'user@cybertec.com.co', 'name' => 'Usuario Cybertec', 'role_type' => 'user', 'initials' => 'UC', 'area' => 'Comercial']);
        User::factory()->create(['email' => 'admin@cybertec.com.co', 'name' => 'Administrador Cybertec', 'role_type' => 'admin', 'initials' => 'AC', 'area' => 'TI']);

        $this->seed();

        $this->assertSame(0, SumateParticipant::whereNull('user_id')->count());
    }

    public function test_every_module_has_a_valid_section_and_type(): void
    {
        $this->seed();

        $modules = Module::all();
        $this->assertGreaterThan(0, $modules->count());

        foreach ($modules as $module) {
            $this->assertContains(
                $module->section,
                Module::SECTIONS,
                "El módulo [{$module->section}/{$module->slug}] tiene una sección fuera de Module::SECTIONS."
            );
            $this->assertContains(
                $module->type,
                Module::TYPES,
                "El módulo [{$module->section}/{$module->slug}] tiene un type fuera de Module::TYPES."
            );
        }
    }

    public function test_every_formulario_module_declares_a_form_slug(): void
    {
        // FormRegistry (F4 Parte B) todavía no existe: por ahora solo se exige que
        // config.formSlug venga no vacío, no que esté registrado en un catálogo real.
        $this->seed();

        $formularios = Module::where('type', 'formulario')->get();
        $this->assertGreaterThan(0, $formularios->count());

        foreach ($formularios as $modulo) {
            $this->assertNotEmpty(
                $modulo->config['formSlug'] ?? null,
                "El módulo formulario [{$modulo->section}/{$modulo->slug}] no declara config.formSlug."
            );
        }
    }

    public function test_every_calendario_module_declares_a_calendar_url(): void
    {
        $this->seed();

        $calendarios = Module::where('type', 'calendario')->get();
        $this->assertGreaterThan(0, $calendarios->count());

        foreach ($calendarios as $modulo) {
            $this->assertNotEmpty(
                $modulo->config['calendarUrl'] ?? null,
                "El módulo calendario [{$modulo->section}/{$modulo->slug}] no declara config.calendarUrl."
            );
        }
    }

    public function test_every_documento_module_declares_a_destination(): void
    {
        $this->seed();

        $documentos = Module::where('type', 'documento')->get();
        $this->assertGreaterThan(0, $documentos->count());

        foreach ($documentos as $modulo) {
            $tieneDestino = ! empty($modulo->href) || ! empty($modulo->config['docs'] ?? null);
            $this->assertTrue(
                $tieneDestino,
                "El módulo documento [{$modulo->section}/{$modulo->slug}] no tiene href ni config.docs."
            );
        }
    }

    public function test_at_least_one_mandatory_course_exists_for_the_sumate_precondition(): void
    {
        // Si esta tabla queda vacía, SumateService::autoContext() da la precondición
        // "capacitaciones" por cumplida a todo el mundo sin querer.
        $this->seed();

        $this->assertTrue(Course::where('tag', 'Obligatorio')->exists());
    }
}
