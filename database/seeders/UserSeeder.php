<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Cuentas de prueba del contrato (01-roles-y-permisos.md / authMock.ts).
        User::updateOrCreate(['email' => 'demo@insumma.co'], [
            'name' => 'Juan Díaz',
            'password' => 'Insumma2026!',
            'role' => 'Colaborador',
            'role_type' => 'user',
            'initials' => 'JD',
            'area' => 'Comercial',
            'phone' => 'Ext. 305',
            'color' => '#2E7D32',
            'joined_at' => '2023-02-15',
            'extension' => '305',
        ]);

        User::updateOrCreate(['email' => 'admin@insumma.co'], [
            'name' => 'Administrador IBG',
            'password' => 'Admin2026#',
            'role' => 'Administrador',
            'role_type' => 'admin',
            'initials' => 'AI',
            'area' => 'TI',
            'phone' => 'Ext. 100',
            'color' => '#1565C0',
            'joined_at' => '2020-01-10',
            'extension' => '100',
        ]);
    }
}
