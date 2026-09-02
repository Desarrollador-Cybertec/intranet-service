<?php

namespace App\Forms;

class FormRegistry
{
    /** @var array<string, class-string<FormDefinition>> */
    private static array $classes = [
        'certificado-laboral' => CertificadoLaboralForm::class,
        'certificado-ingresos-retenciones' => CertificadoIngresosRetencionesForm::class,
        'condiciones-inseguras' => CondicionesInsegurasForm::class,
        'accidente-trabajo' => AccidenteTrabajoForm::class,
    ];

    public static function find(string $slug): ?FormDefinition
    {
        $class = self::$classes[$slug] ?? null;

        return $class ? new $class : null;
    }

    /** @return list<FormDefinition> */
    public static function all(): array
    {
        return array_map(fn (string $class) => new $class, array_values(self::$classes));
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::$classes);
    }
}
