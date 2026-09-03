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
     * que depende CourseSeeder/SumateSeeder.
     */
    public function run(): void
    {
        $this->abortIfProduction();

        $this->call([
            QaSeeder::class,
            DirectorySeeder::class,
            ArticleSeeder::class,
            CourseSeeder::class,
            ModuleSeeder::class,
            SumateSeeder::class,
        ]);
    }
}
