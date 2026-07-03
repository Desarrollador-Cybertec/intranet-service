<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Únicas 2 cuentas de prueba de la app: todo el resto de datos iniciales
     * (Súmate, Ideas, Foro, Capacitaciones) se enlaza a estos 2 usuarios reales.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'user@cybertec.com.co'], [
            'name' => 'Usuario Cybertec',
            'password' => 'Cybertec2026!',
            'role' => 'Colaborador',
            'role_type' => 'user',
            'initials' => 'UC',
            'area' => 'Comercial',
            'phone' => 'Ext. 305',
            'color' => '#2E7D32',
            'joined_at' => '2023-02-15',
            'extension' => '305',
        ]);

        User::updateOrCreate(['email' => 'admin@cybertec.com.co'], [
            'name' => 'Administrador Cybertec',
            'password' => 'Cybertec2026!',
            'role' => 'Administrador',
            'role_type' => 'admin',
            'initials' => 'AC',
            'area' => 'TI',
            'phone' => 'Ext. 100',
            'color' => '#1565C0',
            'joined_at' => '2020-01-10',
            'extension' => '100',
        ]);
    }
}
