<?php

namespace Database\Seeders;

use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class ForumSeeder extends Seeder
{
    public function run(): void
    {
        $authorsByName = User::whereIn('email', [
            'laura.pena@insumma.co', 'maria.castro@insumma.co', 'jorge.morales@insumma.co',
        ])->get()->keyBy('name');

        $posts = [
            [
                'id' => 1,
                'title' => '¿Cuáles son los protocolos actualizados para visitas a granjas en zona de riesgo?',
                'body' => 'Necesito orientación para una visita la próxima semana. ¿El protocolo de agosto sigue vigente o hay uno nuevo para el segundo semestre?',
                'tag' => 'Bioseguridad',
                'tags' => ['Bioseguridad', 'Protocolos', '🔥 Popular'],
                'votes' => 24, 'author' => 'Laura Peña', 'date' => 'Hace 3 horas', 'replies' => 8,
            ],
            [
                'id' => 2,
                'title' => 'Recomendaciones de suplementación para bovinos en época seca',
                'body' => 'Estoy trabajando con un cliente en Lebrija con problemas de baja condición corporal en verano. ¿Algún colega tiene experiencia con el suplemento BM-450?',
                'tag' => 'Nutrición',
                'tags' => ['Nutrición', 'Bovinos'],
                'votes' => 11, 'author' => 'María Castro', 'date' => 'Hace 1 día', 'replies' => 5,
            ],
            [
                'id' => 3,
                'title' => 'Solicitud: canal de comunicación en tiempo real para el equipo de campo',
                'body' => 'Sería muy útil tener comunicación en tiempo real durante las visitas técnicas. ¿Alguien más lo ve necesario? ¿Qué herramienta proponen?',
                'tag' => 'Herramientas',
                'tags' => ['Herramientas', 'Comunicación'],
                'votes' => 7, 'author' => 'Jorge Morales', 'date' => 'Hace 2 días', 'replies' => 12,
            ],
        ];

        foreach ($posts as $p) {
            $p['author_id'] = $authorsByName[$p['author']]->id ?? null;
            ForumPost::updateOrCreate(['id' => $p['id']], $p);
        }
    }
}
