<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\CourseDemoEnrollmentsSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DirectorySeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\QaSeeder;
use Database\Seeders\SumateDemoParticipantsSeeder;
use Database\Seeders\SumateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Fija el contrato de database/seeders/Concerns/RefusesProductionSeeding.php: los
 * seeders de cuentas/datos de prueba deben negarse a correr en producción, pero el
 * contenido real del negocio (módulos, catálogo de Súmate, catálogo de cursos) debe
 * poder aplicarse allá vía `db:seed --class=... --force` — si esto se rompe,
 * RH/SST/SIG/Sintyc/Súmate quedan sin contenido tras desplegar sin que nadie se dé
 * cuenta hasta que un usuario real lo vea vacío (ver Guia_Uso_Intranet_Insumma.md).
 *
 * `--force` es necesario incluso en el test: es lo que evita que el propio
 * ConfirmableTrait de Laravel pida confirmación interactiva al detectar
 * environment()==='production' antes de que nuestro guard se ejecute.
 */
class SeederProductionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_and_test_data_seeders_refuse_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        $blocked = [
            DatabaseSeeder::class,
            QaSeeder::class,
            ArticleSeeder::class,
            DirectorySeeder::class,
            CourseDemoEnrollmentsSeeder::class,
            SumateDemoParticipantsSeeder::class,
        ];

        foreach ($blocked as $seeder) {
            try {
                $this->artisan('db:seed', ['--class' => $seeder, '--force' => true])->run();
                $this->fail("$seeder debía lanzar RuntimeException en producción.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('no debe ejecutarse en producción', $e->getMessage(), $seeder);
            }
        }
    }

    public function test_real_content_seeders_run_in_production(): void
    {
        $this->app['env'] = 'production';

        foreach ([ModuleSeeder::class, CourseSeeder::class, SumateSeeder::class] as $seeder) {
            $this->artisan('db:seed', ['--class' => $seeder, '--force' => true])
                ->assertSuccessful();
        }

        $this->assertSame(0, User::count());
        $this->assertTrue(Module::where('section', 'sintyc')->where('slug', 'sintyc-app')->exists());
    }
}
