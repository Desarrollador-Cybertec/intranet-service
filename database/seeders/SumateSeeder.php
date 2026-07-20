<?php

namespace Database\Seeders;

use App\Models\SumateAccion;
use App\Models\SumateConfig;
use App\Models\SumateNivel;
use App\Models\SumatePrecondicion;
use App\Models\User;
use App\Services\SumateService;
use Illuminate\Database\Seeder;

/**
 * Datos del programa Súmate (sumateMock.ts). Q3 2026.
 * Cada participante se enlaza a un usuario real (users.email); name/initials/color/area
 * se derivan del User, no se duplican, para que sumate_participants.user_id quede
 * siempre íntegro (requiere que los usuarios ya existan: scripts/create-test-users.php).
 */
class SumateSeeder extends Seeder
{
    public function run(SumateService $sumate): void
    {
        SumateConfig::updateOrCreate(['trimestre' => 'Q3 2026'], [
            'periodo_label' => 'Julio – Septiembre 2026',
            'cierre_label' => '30 sep 2026',
            'active' => true,
        ]);

        // auto_source != null → la calcula el servidor; el admin no la marca a mano.
        $precondiciones = [
            ['slug' => 'antiguedad', 'label' => 'Antigüedad', 'req' => '> 3 meses', 'desc' => 'Garantiza que la persona ya conoce mínimamente la empresa, su rol y la forma básica de trabajo.', 'auto_source' => 'antiguedad'],
            ['slug' => 'puntualidad', 'label' => 'Puntualidad', 'req' => 'Máx. 10 min de retraso en el trimestre', 'desc' => 'Refleja disciplina operativa y respeto por los tiempos del equipo y de la organización.', 'auto_source' => null],
            ['slug' => 'asistencia', 'label' => 'Asistencia', 'req' => 'Máx. 1 falla en el trimestre', 'desc' => 'Demuestra continuidad, compromiso y disponibilidad durante el período evaluado.', 'auto_source' => null],
            ['slug' => 'disciplinarios', 'label' => 'Disciplinarios', 'req' => 'Sin disciplinarios ni sanciones', 'desc' => 'Asegura que el reconocimiento parta de un comportamiento laboral adecuado y sin sanciones.', 'auto_source' => null],
            ['slug' => 'capacitaciones', 'label' => 'Capacitaciones', 'req' => '100 % cumplidas', 'desc' => 'Confirma que la persona cuenta con la formación mínima requerida en SIG, SST y su proceso para participar responsablemente.', 'auto_source' => 'capacitaciones'],
        ];
        foreach ($precondiciones as $i => $p) {
            SumatePrecondicion::updateOrCreate(['slug' => $p['slug']], $p + ['position' => $i]);
        }

        $acciones = [
            ['slug' => 'yoAporto', 'label' => 'Yo Aporto', 'icon' => '💡', 'desc' => 'Reporte de hallazgos, no conformidades de proceso y peticiones, quejas o reclamos de clientes.', 'pts_each' => 10, 'max' => 3, 'max_pts' => 30, 'color' => '#2E7D32', 'bg' => '#E8F5E9', 'rango' => '1 a 3 reportes'],
            ['slug' => 'mejora', 'label' => 'Acciones de Mejora', 'icon' => '🔧', 'desc' => 'Propuesta de actividades concretas que corrigen una falla, optimizan un proceso o previenen que un problema se repita.', 'pts_each' => 10, 'max' => 2, 'max_pts' => 20, 'color' => '#1565C0', 'bg' => '#E3F2FD', 'rango' => '1 a 2 propuestas'],
            ['slug' => 'infraestructura', 'label' => 'Reporte de Infraestructura', 'icon' => '🏗️', 'desc' => 'Informes sobre daños, deterioros, necesidades locativas o condiciones físicas que afectan el trabajo, la seguridad o la operación.', 'pts_each' => 10, 'max' => 1, 'max_pts' => 10, 'color' => '#F57C00', 'bg' => '#FFF3E0', 'rango' => '1 reporte'],
            ['slug' => 'inseguras', 'label' => 'Acciones Inseguras', 'icon' => '⚠️', 'desc' => 'Reporte de condiciones o comportamientos inseguros identificados en el entorno de trabajo que puedan generar accidentes.', 'pts_each' => 10, 'max' => 2, 'max_pts' => 20, 'color' => '#C62828', 'bg' => '#FFEBEE', 'rango' => '1 a 2 reportes'],
            ['slug' => 'redes', 'label' => 'Redes Corporativas', 'icon' => '📢', 'desc' => 'Apoyo activo a las redes de la empresa: compartir contenido institucional, participar en publicaciones y promover la cultura Insumma.', 'pts_each' => 10, 'max' => 2, 'max_pts' => 20, 'color' => '#6A1B9A', 'bg' => '#EDE7F6', 'rango' => '1 a 2 participaciones'],
        ];
        foreach ($acciones as $i => $a) {
            SumateAccion::updateOrCreate(['slug' => $a['slug']], $a + ['position' => $i]);
        }

        $niveles = [
            ['nivel' => 1, 'emoji' => '🏆', 'label' => 'Nivel 1', 'min' => 100, 'max' => 100, 'color' => '#2E7D32', 'bg' => '#E8F5E9', 'beneficio' => 'Teletrabajo siguiente trimestre o 1 día libre + Bono $600.000 COP', 'condicion' => 'Para teletrabajar, el rol debe ser elegible. En otro caso se tendrá un día libre durante el trimestre. Se puede ganar máximo 2 veces por año.'],
            ['nivel' => 2, 'emoji' => '🥈', 'label' => 'Nivel 2', 'min' => 95, 'max' => 99, 'color' => '#6A1B9A', 'bg' => '#EDE7F6', 'beneficio' => 'Teletrabajo siguiente trimestre o 1 día libre + Bono $300.000 COP', 'condicion' => 'Para teletrabajar, el rol debe ser elegible. En otro caso se tendrá un día libre durante el trimestre. Se puede ganar máximo 2 veces por año.'],
            ['nivel' => 3, 'emoji' => '🥉', 'label' => 'Nivel 3', 'min' => 85, 'max' => 94, 'color' => '#1565C0', 'bg' => '#E3F2FD', 'beneficio' => '1 día completo de descanso remunerado', 'condicion' => 'Para roles elegibles.'],
            ['nivel' => 4, 'emoji' => '⭐', 'label' => 'Nivel 4', 'min' => 70, 'max' => 84, 'color' => '#F57C00', 'bg' => '#FFF3E0', 'beneficio' => 'Medio día en cumpleaños', 'condicion' => 'Para roles elegibles. Si ya cumplió años, el colaborador puede escoger el medio día libremente.'],
        ];
        foreach ($niveles as $n) {
            SumateNivel::updateOrCreate(['nivel' => $n['nivel']], $n);
        }

        $participantes = [
            // Solo las manuales: antigüedad y capacitaciones las deriva SumateService.
            ['email' => 'user@cybertec.com.co', 'pre' => ['puntualidad' => true, 'asistencia' => true, 'disciplinarios' => true], 'acc' => ['yoAporto' => 3, 'mejora' => 2, 'infraestructura' => 1, 'inseguras' => 2, 'redes' => 2]],
            ['email' => 'admin@cybertec.com.co', 'pre' => ['puntualidad' => true, 'asistencia' => true, 'disciplinarios' => false], 'acc' => ['yoAporto' => 2, 'mejora' => 1, 'infraestructura' => 0, 'inseguras' => 1, 'redes' => 1]],
        ];

        $usersByEmail = User::whereIn('email', collect($participantes)->pluck('email')->unique())
            ->get()
            ->keyBy('email');

        $accionIds = SumateAccion::pluck('id', 'slug');

        foreach ($participantes as $data) {
            $user = $usersByEmail[$data['email']] ?? null;

            if (! $user) {
                throw new \RuntimeException("SumateSeeder: no existe el usuario {$data['email']}. Corre php artisan users:import primero.");
            }

            $participant = $sumate->syncParticipantFor($user);
            $sumate->setPreconditions($participant, $data['pre']);

            foreach ($data['acc'] as $slug => $count) {
                $participant->actionCounts()->updateOrCreate(
                    ['accion_id' => $accionIds[$slug]],
                    ['count' => $count],
                );
            }
        }
    }
}
