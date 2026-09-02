<?php

namespace App\Console\Commands;

use App\Services\DirectoryImportService;
use Illuminate\Console\Command;

/**
 * Importa/actualiza el Directorio institucional desde un CSV
 * (columnas: Nombre, Área, Cargo, Teléfono, Extensión, Correo).
 */
class ImportDirectoryCommand extends Command
{
    protected $signature = 'directory:import
        {file : Ruta al CSV (UTF-8) con las columnas Nombre, Área, Cargo, Teléfono, Extensión, Correo}
        {--dry-run : Analiza el archivo y muestra el resumen sin escribir en la base de datos}';

    protected $description = 'Importa o actualiza personas del Directorio institucional desde un CSV.';

    public function handle(DirectoryImportService $importer): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("No se encontró el archivo: {$path}");

            return self::FAILURE;
        }

        $rows = $importer->readCsv($path);

        if ($rows === null) {
            $this->error("No se pudo abrir el archivo: {$path}");

            return self::FAILURE;
        }

        $result = $importer->import($rows, (bool) $this->option('dry-run'));

        $this->newLine();
        $this->table(
            ['Resultado', 'Filas'],
            [
                ['Creadas', $result['created']],
                ['Actualizadas', $result['updated']],
                ['Omitidas', count($result['skipped'])],
                ['Total en archivo', count($rows)],
            ],
        );

        if ($result['skipped'] !== []) {
            $this->newLine();
            $this->warn('Filas omitidas:');
            $this->table(['Línea', 'Valor', 'Motivo'], $result['skipped']);
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->comment('Modo --dry-run: no se escribió nada en la base de datos.');
        }

        return self::SUCCESS;
    }
}
