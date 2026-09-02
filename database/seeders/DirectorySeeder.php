<?php

namespace Database\Seeders;

use App\Models\DirectoryPerson;
use App\Models\User;
use App\Services\DirectoryService;
use Illuminate\Database\Seeder;

/**
 * Sincroniza el Directorio con los usuarios ya sembrados (QaSeeder debe correr
 * antes) y agrega un par de personas SIN cuenta, para que el caso "existe con o
 * sin cuenta" (D4 de la matriz de pendientes) sea observable desde el arranque.
 */
class DirectorySeeder extends Seeder
{
    public function run(DirectoryService $directory): void
    {
        User::where('active', true)->whereNotNull('profile_completed_at')->each(
            fn (User $user) => $directory->syncFromUser($user)
        );

        DirectoryPerson::updateOrCreate(
            ['email' => 'porteria@insumma.co'],
            [
                'name' => 'Wilson Cárdenas', 'area' => 'Seguridad Física', 'role' => 'Vigilante',
                'phone' => '3001112233', 'active' => true, 'position' => 900,
                'initials' => 'WC', 'color' => '#455A64',
            ],
        );

        DirectoryPerson::updateOrCreate(
            ['email' => 'aseo@insumma.co'],
            [
                'name' => 'Rosa Elena Duarte', 'area' => 'Servicios Generales', 'role' => 'Auxiliar de Aseo',
                'phone' => '3004445566', 'active' => true, 'position' => 901,
                'initials' => 'RD', 'color' => '#6D4C41',
            ],
        );
    }
}
