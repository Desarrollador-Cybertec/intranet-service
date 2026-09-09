<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\RefusesProductionSeeding;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use RefusesProductionSeeding, WithoutModelEvents;

    /**
     * Datos de ejemplo del contrato (mocks) para que el front funcione sin cambios.
     * QaSeeder va primero: crea los usuarios (incluidos los @cybertec.com.co) de los
     * que dependen CourseDemoEnrollmentsSeeder/SumateDemoParticipantsSeeder.
     *
     * Este runner completo (y cada seeder *Demo*, más QaSeeder/DirectorySeeder/
     * ArticleSeeder) se niega a correr en producción — son cuentas y contenido de
     * prueba. CourseSeeder/ModuleSeeder/SumateSeeder sí son contenido real del
     * negocio y deben aplicarse allá, uno por uno vía `db:seed --class=`
     * (ver Guia_Uso_Intranet_Insumma.md).
     */
    public function run(): void
    {
        $this->abortIfProduction();

        $this->call([
            QaSeeder::class,
            DirectorySeeder::class,
            ArticleSeeder::class,
            CourseSeeder::class,
            CourseDemoEnrollmentsSeeder::class,
            ModuleSeeder::class,
            SumateSeeder::class,
            SumateDemoParticipantsSeeder::class,
        ]);
    }
}
