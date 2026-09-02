<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Importa completados de cursos desde un CSV (columnas: Correo, Curso, Completado),
 * para cargar en bloque el historial de capacitaciones y no depender de marcarlas
 * una por una desde el panel admin de Súmate.
 */
class ImportCourseCompletionsCommand extends Command
{
    protected $signature = 'sumate:import-capacitaciones
        {file : Ruta al CSV (UTF-8) con las columnas Correo, Curso, Completado}
        {--dry-run : Analiza el archivo y muestra el resumen sin escribir en la base de datos}';

    protected $description = 'Importa completados de cursos (precondición Súmate "capacitaciones") desde un CSV.';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("No se encontró el archivo: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error("No se pudo abrir el archivo: {$path}");

            return self::FAILURE;
        }

        $coursesByLabel = Course::all()->keyBy(fn (Course $c) => mb_strtolower(trim($c->label)));
        $usersByEmail = User::all()->keyBy(fn (User $u) => mb_strtolower(trim($u->email)));

        $updated = 0;
        $skipped = [];
        $line = 0;
        $dryRun = (bool) $this->option('dry-run');

        while (($cells = fgetcsv($handle, escape: '')) !== false) {
            $line++;
            if ($line === 1) {
                continue; // cabecera
            }

            [$correo, $curso, $completado] = array_pad(
                array_map(fn ($c) => trim(str_replace("\u{FEFF}", '', (string) $c)), $cells),
                3,
                '',
            );

            $user = $usersByEmail[mb_strtolower($correo)] ?? null;
            $course = $coursesByLabel[mb_strtolower($curso)] ?? null;

            if (! $user) {
                $skipped[] = [$line, $correo, 'Correo no encontrado'];

                continue;
            }
            if (! $course) {
                $skipped[] = [$line, $curso, 'Curso no encontrado'];

                continue;
            }

            $completed = in_array(mb_strtolower($completado), ['si', 'sí', 'true', '1', 'yes'], true);

            if (! $dryRun) {
                $course->enrollments()->updateOrCreate(['user_id' => $user->id], ['completed' => $completed]);
            }
            $updated++;
        }

        fclose($handle);

        $this->newLine();
        $this->table(['Resultado', 'Filas'], [
            ['Actualizadas', $updated],
            ['Omitidas', count($skipped)],
        ]);

        if ($skipped !== []) {
            $this->newLine();
            $this->warn('Filas omitidas:');
            $this->table(['Línea', 'Valor', 'Motivo'], $skipped);
        }

        if ($dryRun) {
            $this->newLine();
            $this->comment('Modo --dry-run: no se escribió nada en la base de datos.');
        }

        return self::SUCCESS;
    }
}
