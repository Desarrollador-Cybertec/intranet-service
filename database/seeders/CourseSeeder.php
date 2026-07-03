<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['id' => 1, 'label' => 'Inducción corporativa', 'icon' => '🏢', 'tag' => 'Obligatorio', 'tag_color' => '#C62828', 'tag_bg' => '#FFEBEE', 'desc' => 'Conoce la misión, visión, valores y estructura organizacional de Insumma Business Group.', 'duration' => '4 horas', 'modality' => 'Virtual'],
            ['id' => 2, 'label' => 'Bioseguridad y manejo de productos', 'icon' => '🧪', 'tag' => 'Obligatorio', 'tag_color' => '#C62828', 'tag_bg' => '#FFEBEE', 'desc' => 'Protocolos de bioseguridad, uso de EPP y manejo seguro de productos veterinarios.', 'duration' => '8 horas', 'modality' => 'Mixto'],
            ['id' => 3, 'label' => 'Liderazgo y trabajo en equipo', 'icon' => '🤝', 'tag' => 'Desarrollo', 'tag_color' => '#1565C0', 'tag_bg' => '#E3F2FD', 'desc' => 'Habilidades blandas para la colaboración efectiva, comunicación asertiva y liderazgo situacional.', 'duration' => '6 horas', 'modality' => 'Presencial'],
            ['id' => 4, 'label' => 'Excel avanzado para gestión', 'icon' => '📊', 'tag' => 'Técnico', 'tag_color' => '#2E7D32', 'tag_bg' => '#E8F5E9', 'desc' => 'Tablas dinámicas, macros básicas y dashboards de seguimiento para áreas administrativas.', 'duration' => '12 horas', 'modality' => 'Virtual'],
            ['id' => 5, 'label' => 'Servicio al cliente interno', 'icon' => '⭐', 'tag' => 'Desarrollo', 'tag_color' => '#1565C0', 'tag_bg' => '#E3F2FD', 'desc' => 'Orientación al servicio, manejo de conflictos y cultura de calidad en procesos internos.', 'duration' => '4 horas', 'modality' => 'Virtual'],
            ['id' => 6, 'label' => 'Normativa ambiental y SST', 'icon' => '🌿', 'tag' => 'Obligatorio', 'tag_color' => '#C62828', 'tag_bg' => '#FFEBEE', 'desc' => 'Marco legal ambiental colombiano, gestión de residuos y cumplimiento regulatorio 2026.', 'duration' => '6 horas', 'modality' => 'Mixto'],
        ];

        foreach ($courses as $c) {
            Course::updateOrCreate(['id' => $c['id']], $c);
        }

        // Usuario Cybertec (usuario de prueba) tiene la Inducción corporativa completada (curso id 1).
        $user = User::where('email', 'user@cybertec.com.co')->first();
        if ($user) {
            $user->enrollments()->updateOrCreate(
                ['course_id' => 1],
                ['completed' => true],
            );
        }
    }
}
