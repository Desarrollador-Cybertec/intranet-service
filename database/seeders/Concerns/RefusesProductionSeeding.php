<?php

namespace Database\Seeders\Concerns;

use RuntimeException;

/**
 * Estos seeders crean cuentas con contraseñas fijas conocidas (ver QaSeeder) y
 * sobreescriben contenido real por clave natural (slug/id) vía updateOrCreate.
 * Ningún proceso automatizado del repo los invoca en producción, pero nada impide
 * que alguien corra `db:seed` a mano apuntando a la base real — este guard lo
 * convierte en un error explícito en vez de un incidente silencioso.
 */
trait RefusesProductionSeeding
{
    private function abortIfProduction(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                static::class.' no debe ejecutarse en producción: crea/sobrescribe datos de prueba.'
            );
        }
    }
}
