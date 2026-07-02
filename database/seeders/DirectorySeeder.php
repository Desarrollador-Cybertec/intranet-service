<?php

namespace Database\Seeders;

use App\Models\DirectoryPerson;
use Illuminate\Database\Seeder;

class DirectorySeeder extends Seeder
{
    public function run(): void
    {
        $people = [
            ['id' => 1, 'name' => 'Laura Peña', 'role' => 'Coordinadora HSEQ', 'area' => 'Bioseguridad', 'image' => '/assets/img/directorio/laura-pena.jpg', 'initials' => 'LP', 'color' => '#2E7D32', 'email' => 'laura.pena@insumma.co', 'phone' => 'Ext. 201'],
            ['id' => 2, 'name' => 'María Castro', 'role' => 'Nutricionista', 'area' => 'Nutrición Animal', 'image' => '/assets/img/directorio/maria-castro.jpg', 'initials' => 'MC', 'color' => '#F57C00', 'email' => 'maria.castro@insumma.co', 'phone' => 'Ext. 205'],
            ['id' => 3, 'name' => 'Roberto Pardo', 'role' => 'Técnico Industrial', 'area' => 'Metalmecánica', 'image' => '/assets/img/directorio/roberto-pardo.jpg', 'initials' => 'RP', 'color' => '#1565C0', 'email' => 'roberto.pardo@insumma.co', 'phone' => 'Ext. 318'],
            ['id' => 4, 'name' => 'Andrea Gómez', 'role' => 'Médica Veterinaria', 'area' => 'Salud Animal', 'image' => '/assets/img/directorio/andrea-gomez.jpg', 'initials' => 'AG', 'color' => '#6A1B9A', 'email' => 'andrea.gomez@insumma.co', 'phone' => 'Ext. 210'],
            ['id' => 5, 'name' => 'Jorge Morales', 'role' => 'Asesor Comercial', 'area' => 'Comercial', 'image' => '/assets/img/directorio/jorge-morales.jpg', 'initials' => 'JM', 'color' => '#00695C', 'email' => 'jorge.morales@insumma.co', 'phone' => 'Ext. 305'],
            ['id' => 6, 'name' => 'Sandra Ruiz', 'role' => 'Analista RRHH', 'area' => 'Gestión Humana', 'image' => '/assets/img/directorio/sandra-ruiz.jpg', 'initials' => 'SR', 'color' => '#C62828', 'email' => 'sandra.ruiz@insumma.co', 'phone' => 'Ext. 102'],
            ['id' => 7, 'name' => 'Juan Díaz', 'role' => 'Asesor Comercial', 'area' => 'Comercial', 'image' => null, 'initials' => 'JD', 'color' => '#388E3C', 'email' => 'juan.diaz@insumma.co', 'phone' => 'Ext. 306'],
            ['id' => 8, 'name' => 'Carlos Vargas', 'role' => 'Operario de Producción', 'area' => 'Producción', 'image' => null, 'initials' => 'CV', 'color' => '#37474F', 'email' => 'carlos.vargas@insumma.co', 'phone' => 'Ext. 412'],
        ];

        foreach ($people as $p) {
            DirectoryPerson::updateOrCreate(['id' => $p['id']], $p);
        }
    }
}
