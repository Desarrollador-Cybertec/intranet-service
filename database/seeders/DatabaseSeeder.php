<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Datos de ejemplo del contrato (mocks) para que el front funcione sin cambios.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ArticleSeeder::class,
            DirectorySeeder::class,
            ForumSeeder::class,
            IdeaSeeder::class,
            CourseSeeder::class,
            ModuleSeeder::class,
            SumateSeeder::class,
        ]);
    }
}
