<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Database\Seeders\Concerns\RefusesProductionSeeding;
use Illuminate\Database\Seeder;

/**
 * Inscripciones de prueba sobre el catálogo real de CourseSeeder, atadas a las
 * cuentas QA/@cybertec.com.co. Requiere que CourseSeeder y QaSeeder ya hayan corrido.
 */
class CourseDemoEnrollmentsSeeder extends Seeder
{
    use RefusesProductionSeeding;

    public function run(): void
    {
        $this->abortIfProduction();

        // Los obligatorios completos son precondición automática de Súmate (capacitaciones):
        // sin al menos un usuario con el 100% completado, ningún participante puede ser
        // elegible nunca y el flujo de aprobación no se puede probar de punta a punta.
        $obligatorioIds = Course::where('tag', 'Obligatorio')->pluck('id');
        $elegibles = User::whereIn('email', ['user@cybertec.com.co', 'demo@insumma.co'])->get();
        foreach ($elegibles as $user) {
            foreach ($obligatorioIds as $courseId) {
                $user->enrollments()->updateOrCreate(
                    ['course_id' => $courseId],
                    ['completed' => true],
                );
            }
        }

        // "Enrolled but not completed" queda representado con otro curso, para que la UI
        // de Súmate/RH también muestre progreso parcial real, no solo 0% o 100%.
        $adminCybertec = User::where('email', 'admin@cybertec.com.co')->first();
        if ($adminCybertec) {
            $adminCybertec->enrollments()->updateOrCreate(['course_id' => 1], ['completed' => true]);
            $adminCybertec->enrollments()->updateOrCreate(['course_id' => 2], ['completed' => false]);
        }
    }
}
