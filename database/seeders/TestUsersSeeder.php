<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TestUsersSeeder extends Seeder
{
    /**
     * Usuarios de prueba adicionales para QA (roles y áreas variadas).
     * Contraseña por defecto para todos: Prueba2026!
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Laura Peña', 'email' => 'laura.pena@insumma.co', 'role' => 'Coordinadora HSEQ', 'role_type' => 'admin', 'initials' => 'LP', 'area' => 'Bioseguridad', 'phone' => 'Ext. 201', 'color' => '#2E7D32', 'joined_at' => '2021-03-01', 'extension' => '201'],
            ['name' => 'María Castro', 'email' => 'maria.castro@insumma.co', 'role' => 'Nutricionista', 'role_type' => 'user', 'initials' => 'MC', 'area' => 'Nutrición Animal', 'phone' => 'Ext. 205', 'color' => '#F57C00', 'joined_at' => '2021-06-15', 'extension' => '205'],
            ['name' => 'Roberto Pardo', 'email' => 'roberto.pardo@insumma.co', 'role' => 'Técnico Industrial', 'role_type' => 'user', 'initials' => 'RP', 'area' => 'Metalmecánica', 'phone' => 'Ext. 318', 'color' => '#1565C0', 'joined_at' => '2022-01-10', 'extension' => '318'],
            ['name' => 'Andrea Gómez', 'email' => 'andrea.gomez@insumma.co', 'role' => 'Médica Veterinaria', 'role_type' => 'user', 'initials' => 'AG', 'area' => 'Salud Animal', 'phone' => 'Ext. 210', 'color' => '#6A1B9A', 'joined_at' => '2022-04-20', 'extension' => '210'],
            ['name' => 'Diana Moreno', 'email' => 'diana.moreno@insumma.co', 'role' => 'Auxiliar de Salud Animal', 'role_type' => 'user', 'initials' => 'DM', 'area' => 'Salud Animal', 'phone' => 'Ext. 211', 'color' => '#00897B', 'joined_at' => '2022-08-01', 'extension' => '211'],
            ['name' => 'Jorge Morales', 'email' => 'jorge.morales@insumma.co', 'role' => 'Asesor Comercial', 'role_type' => 'user', 'initials' => 'JM', 'area' => 'Comercial', 'phone' => 'Ext. 305', 'color' => '#00695C', 'joined_at' => '2020-09-05', 'extension' => '305'],
            ['name' => 'Sandra Ruiz', 'email' => 'sandra.ruiz@insumma.co', 'role' => 'Analista RRHH', 'role_type' => 'admin', 'initials' => 'SR', 'area' => 'Gestión Humana', 'phone' => 'Ext. 102', 'color' => '#C62828', 'joined_at' => '2019-11-12', 'extension' => '102'],
            ['name' => 'Carlos Vargas', 'email' => 'carlos.vargas@insumma.co', 'role' => 'Operario de Producción', 'role_type' => 'user', 'initials' => 'CV', 'area' => 'Producción', 'phone' => 'Ext. 412', 'color' => '#37474F', 'joined_at' => '2023-02-01', 'extension' => '412'],
            ['name' => 'Felipe Castro', 'email' => 'felipe.castro@insumma.co', 'role' => 'Analista de Sistemas', 'role_type' => 'user', 'initials' => 'FC', 'area' => 'Sistemas', 'phone' => 'Ext. 407', 'color' => '#0277BD', 'joined_at' => '2021-10-18', 'extension' => '407'],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['password' => 'Prueba2026!'])
            );
        }
    }
}
