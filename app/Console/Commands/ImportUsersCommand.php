<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SumateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Importa la nómina desde el CSV exportado de la hoja de cálculo
 * (columnas: Nombre Mostrado, Correo Electronico, Contraseña).
 *
 * La hoja NO trae cargo, área, teléfono ni fecha de ingreso: esos campos quedan
 * en null y el usuario los completa en su primer inicio de sesión
 * (ver App\Http\Middleware\EnsureProfileCompleted).
 */
class ImportUsersCommand extends Command
{
    protected $signature = 'users:import
        {file : Ruta al CSV (UTF-8) con las columnas Nombre Mostrado, Correo Electronico, Contraseña}
        {--dry-run : Analiza el archivo y muestra el resumen sin escribir en la base de datos}
        {--admin-domain=cybertec.com.co : Los correos de este dominio se importan con role_type=admin}';

    protected $description = 'Importa usuarios desde un CSV de nómina (usuario + contraseña), dejando el perfil incompleto.';

    public function handle(SumateService $sumate): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("No se encontró el archivo: {$path}");

            return self::FAILURE;
        }

        $rows = $this->readCsv($path);

        if ($rows === null) {
            return self::FAILURE;
        }

        [$valid, $skipped] = $this->partition($rows);

        $created = 0;
        $updated = 0;

        if ($this->option('dry-run')) {
            foreach ($valid as $row) {
                User::where('email', $row['email'])->exists() ? $updated++ : $created++;
            }
        } else {
            DB::transaction(function () use ($valid, $sumate, &$created, &$updated) {
                foreach ($valid as $row) {
                    $existing = User::where('email', $row['email'])->first();

                    if ($existing) {
                        // No pisamos contraseña ni perfil ya diligenciado de usuarios existentes.
                        $updated++;
                        $user = $existing;
                    } else {
                        $user = User::create([
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'password' => $row['password'], // el cast 'hashed' lo encripta
                            'role' => null,
                            'role_type' => $row['roleType'],
                            'initials' => User::initialsFrom($row['name']),
                            'area' => null,
                            'phone' => null,
                            'color' => User::colorFrom($row['email']),
                            'joined_at' => null,
                            'extension' => null,
                            'profile_completed_at' => null,
                        ]);
                        $created++;
                    }

                    $sumate->syncParticipantFor($user);
                }
            });
        }

        $this->newLine();
        $this->table(
            ['Resultado', 'Filas'],
            [
                ['Creados', $created],
                ['Ya existían (sin cambios)', $updated],
                ['Omitidos', count($skipped)],
                ['Total en archivo', count($rows)],
            ],
        );

        if ($skipped !== []) {
            $this->newLine();
            $this->warn('Filas omitidas:');
            $this->table(['Línea', 'Valor', 'Motivo'], $skipped);
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->comment('Modo --dry-run: no se escribió nada en la base de datos.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{line:int,name:string,email:string,password:string}>|null
     */
    private function readCsv(string $path): ?array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            $this->error("No se pudo abrir el archivo: {$path}");

            return null;
        }

        $rows = [];
        $line = 0;

        while (($cells = fgetcsv($handle, escape: '')) !== false) {
            $line++;

            if ($line === 1) {
                continue; // cabecera
            }

            // La primera celda del archivo puede traer BOM UTF-8.
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
                'email' => Str::lower($cells[1] ?? ''),
                'password' => $cells[2] ?? '',
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Separa filas válidas de omitidas y normaliza nombre y rol.
     *
     * @param  list<array{line:int,name:string,email:string,password:string}>  $rows
     * @return array{0:list<array{name:string,email:string,password:string,roleType:string}>,1:list<array{int,string,string}>}
     */
    private function partition(array $rows): array
    {
        $adminDomain = Str::lower((string) $this->option('admin-domain'));

        $valid = [];
        $skipped = [];
        $seen = [];

        foreach ($rows as $row) {
            if (! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $skipped[] = [$row['line'], $row['email'] ?: '(vacío)', 'Correo inválido'];

                continue;
            }

            if ($row['password'] === '') {
                $skipped[] = [$row['line'], $row['email'], 'Sin contraseña'];

                continue;
            }

            if (isset($seen[$row['email']])) {
                $skipped[] = [$row['line'], $row['email'], "Duplicado de la línea {$seen[$row['email']]}"];

                continue;
            }

            $seen[$row['email']] = $row['line'];

            $valid[] = [
                'name' => $this->displayName($row['name'], $row['email']),
                'email' => $row['email'],
                'password' => $row['password'],
                'roleType' => Str::endsWith($row['email'], '@'.$adminDomain) ? 'admin' : 'user',
            ];
        }

        return [$valid, $skipped];
    }

    /**
     * Algunas filas traen el correo como nombre (o vienen vacías):
     * en ese caso se humaniza la parte local hasta que el usuario lo corrija.
     */
    private function displayName(string $name, string $email): string
    {
        if ($name !== '' && Str::lower($name) !== $email) {
            return $name;
        }

        $local = Str::before($email, '@');

        return Str::headline(str_replace(['.', '_', '-'], ' ', $local));
    }
}
