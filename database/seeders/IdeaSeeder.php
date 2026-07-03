<?php

namespace Database\Seeders;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Database\Seeder;

class IdeaSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user@cybertec.com.co')->first();
        $admin = User::where('email', 'admin@cybertec.com.co')->first();

        // La idea anónima conserva author_id real (auditoría) aunque se muestre "Anónimo".
        Idea::updateOrCreate(['id' => 1], [
            'category' => 'Bienestar laboral',
            'title' => 'Sistema de carpooling entre colaboradores de Girón',
            'description' => 'Organizar rutas compartidas reduciría costos de transporte y la huella de carbono del grupo.',
            'anonymous' => true,
            'votes' => 12,
            'date' => 'Hace 3 días',
            'author_id' => $user?->id,
            'author' => 'Usuario Cybertec',
            'status' => 'pendiente',
        ]);

        Idea::updateOrCreate(['id' => 2], [
            'category' => 'Sostenibilidad',
            'title' => 'Puntos de reciclaje diferenciado en todas las áreas',
            'description' => 'Instalar puntos de separación de residuos en todas las áreas de trabajo de la planta con señalización por color.',
            'anonymous' => false,
            'votes' => 8,
            'date' => 'Hace 5 días',
            'author_id' => $admin?->id,
            'author' => 'Administrador Cybertec',
            'status' => 'pendiente',
        ]);
    }
}
