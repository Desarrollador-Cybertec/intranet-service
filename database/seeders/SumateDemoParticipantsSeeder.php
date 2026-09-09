<?php

namespace Database\Seeders;

use App\Models\SumateAccion;
use App\Models\User;
use App\Services\SumateService;
use Database\Seeders\Concerns\RefusesProductionSeeding;
use Illuminate\Database\Seeder;

/**
 * Participantes de prueba sobre el catálogo real de SumateSeeder, atados a las
 * cuentas QA/@cybertec.com.co. Requiere que SumateSeeder y QaSeeder ya hayan corrido.
 */
class SumateDemoParticipantsSeeder extends Seeder
{
    use RefusesProductionSeeding;

    public function run(SumateService $sumate): void
    {
        $this->abortIfProduction();

        $participantes = [
            // Solo las manuales: antigüedad y capacitaciones las deriva SumateService.
            // yoAporto/mejora/infraestructura quedan al tope (3/2/1): sirve para probar
            // que aprobar una solicitud por encima del tope de su acción otorga 0 puntos.
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
                throw new \RuntimeException("SumateDemoParticipantsSeeder: no existe el usuario {$data['email']}. Corre php artisan users:import primero.");
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

        // Cuenta QA @insumma.co elegible y sin acciones aún: caso feliz de autogestión
        // (reportar → aprobar → puntos visibles) sin depender de las cuentas legacy
        // @cybertec.com.co. Opcional a propósito (no lanza si falta): a diferencia de
        // los dos participantes de arriba, varios tests siembran este seeder aislado
        // (sin QaSeeder primero) y ahí "demo@insumma.co" nunca existe.
        $demo = User::where('email', 'demo@insumma.co')->first();
        if ($demo) {
            $participant = $sumate->syncParticipantFor($demo);
            $sumate->setPreconditions($participant, ['puntualidad' => true, 'asistencia' => true, 'disciplinarios' => true]);
        }
    }
}
