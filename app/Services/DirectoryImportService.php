<?php

namespace App\Services;

use App\Models\DirectoryPerson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CSV compartido por `directory:import` (artisan) y POST /api/directory/entries/import.
 * Cabeceras esperadas: Nombre, Área, Cargo, Teléfono, Extensión, Correo.
 */
class DirectoryImportService
{
    /**
     * @return list<array{line:int,name:string,area:string,role:string,phone:string,extension:string,email:string}>|null
     */
    public function readCsv(string $path): ?array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return null;
        }

        $rows = [];
        $line = 0;

        while (($cells = fgetcsv($handle, escape: '')) !== false) {
            $line++;

            if ($line === 1) {
                continue; // cabecera
            }

            $cells = array_map(
                fn ($c) => trim(str_replace("\u{FEFF}", '', (string) $c)),
                $cells,
            );

            if (implode('', $cells) === '') {
                continue; // línea en blanco
            }

            $rows[] = [
                'line' => $line,
                'name' => $cells[0] ?? '',
                'area' => $cells[1] ?? '',
                'role' => $cells[2] ?? '',
                'phone' => $cells[3] ?? '',
                'extension' => $cells[4] ?? '',
                'email' => Str::lower($cells[5] ?? ''),
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * `$dryRun`: valida y cuenta pero no escribe nada en la base de datos.
     *
     * @param  list<array{line:int,name:string,area:string,role:string,phone:string,extension:string,email:string}>  $rows
     * @return array{created:int,updated:int,skipped:list<array{0:int,1:string,2:string}>}
     */
    public function import(array $rows, bool $dryRun = false): array
    {
        $run = function () use ($rows, $dryRun) {
            $created = 0;
            $updated = 0;
            $skipped = [];
            $seen = [];

            foreach ($rows as $row) {
                if ($row['name'] === '') {
                    $skipped[] = [$row['line'], $row['email'] ?: '(sin correo)', 'Falta el nombre'];

                    continue;
                }

                if ($row['email'] !== '' && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $skipped[] = [$row['line'], $row['email'], 'Correo inválido'];

                    continue;
                }

                if ($row['email'] !== '') {
                    if (isset($seen[$row['email']])) {
                        $skipped[] = [$row['line'], $row['email'], "Duplicado de la línea {$seen[$row['email']]}"];

                        continue;
                    }
                    $seen[$row['email']] = $row['line'];
                }

                $existing = $row['email'] !== ''
                    ? DirectoryPerson::whereRaw('LOWER(email) = ?', [$row['email']])->first()
                    : null;

                $attributes = [
                    'name' => $row['name'],
                    'area' => $row['area'] ?: null,
                    'role' => $row['role'] ?: null,
                    'phone' => $row['phone'] ?: null,
                    'extension' => $row['extension'] ?: null,
                    'email' => $row['email'] ?: null,
                ];

                if ($existing) {
                    if (! $dryRun) {
                        $existing->fill($attributes)->save();
                    }
                    $updated++;

                    continue;
                }

                if (! $dryRun) {
                    $linkedUser = $row['email'] !== ''
                        ? User::whereRaw('LOWER(email) = ?', [$row['email']])->first()
                        : null;

                    DirectoryPerson::create($attributes + [
                        'user_id' => $linkedUser?->id,
                        'initials' => User::initialsFrom($row['name']),
                        'color' => User::colorFrom($row['email'] ?: $row['name']),
                        'active' => true,
                        'position' => 0,
                    ]);
                }
                $created++;
            }

            return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
        };

        return $dryRun ? $run() : DB::transaction($run);
    }
}
