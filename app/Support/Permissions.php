<?php

namespace App\Support;

/**
 * Catálogo de vistas y acciones de la matriz de permisos. Las VISTAS son un catálogo
 * de código (cambian con un deploy); los ROLES y sus permisos son dato (se administran
 * desde Configuraciones sin deploy). No confundir ambos.
 */
class Permissions
{
    public const ACTIONS = ['ver', 'crear', 'editar', 'eliminar'];

    /**
     * vista => acciones que de verdad hacen algo en esa vista. El resto de acciones
     * se pinta como "—" (deshabilitado) en la matriz: no tiene sentido un checkbox que
     * no gatea nada. `ver` siempre está presente: es lo que habilita/deshabilita la vista.
     *
     * @var array<string, list<string>>
     */
    private const VIEW_ACTIONS = [
        'inicio' => ['ver'],
        'enterate' => ['ver', 'crear', 'editar', 'eliminar'],
        'directorio' => ['ver', 'crear', 'editar', 'eliminar'],
        'calendario' => ['ver', 'crear', 'editar', 'eliminar'],
        'salas' => ['ver', 'crear'],
        'sumate' => ['ver', 'editar'],
        'rh' => ['ver', 'crear', 'editar', 'eliminar'],
        'sst' => ['ver', 'crear', 'editar', 'eliminar'],
        'sig' => ['ver', 'crear', 'editar', 'eliminar'],
        'sintyc' => ['ver', 'crear', 'editar', 'eliminar'],
        'usuarios' => ['ver', 'crear', 'editar'],
        'configuraciones' => ['ver', 'crear', 'editar', 'eliminar'],
    ];

    /** @var array<string,string> */
    private const VIEW_LABELS = [
        'inicio' => 'Inicio',
        'enterate' => 'Entérate',
        'directorio' => 'Directorio',
        'calendario' => 'Calendario',
        'salas' => 'Salas',
        'sumate' => 'Súmate',
        'rh' => 'Recursos Humanos',
        'sst' => 'SST',
        'sig' => 'SIG',
        'sintyc' => 'S!NTyC',
        'usuarios' => 'Usuarios',
        'configuraciones' => 'Configuraciones',
    ];

    /** @var array<string,string> */
    private const ACTION_LABELS = [
        'ver' => 'Ver',
        'crear' => 'Crear',
        'editar' => 'Editar',
        'eliminar' => 'Eliminar',
    ];

    /** @return list<string> */
    public static function views(): array
    {
        return array_keys(self::VIEW_ACTIONS);
    }

    public static function isValidView(string $view): bool
    {
        return array_key_exists($view, self::VIEW_ACTIONS);
    }

    public static function isValid(string $view, string $action): bool
    {
        return self::isValidView($view) && in_array($action, self::VIEW_ACTIONS[$view], true);
    }

    /**
     * Catálogo para la UI de Configuraciones: una fila por vista con las acciones
     * que de verdad aplican (el resto se pinta deshabilitado).
     *
     * @return list<array{id:string,label:string,actions:list<string>}>
     */
    public static function catalog(): array
    {
        return array_map(
            fn (string $view) => [
                'id' => $view,
                'label' => self::VIEW_LABELS[$view],
                'actions' => self::VIEW_ACTIONS[$view],
            ],
            self::views(),
        );
    }

    /** @return list<array{id:string,label:string}> */
    public static function actionCatalog(): array
    {
        return array_map(
            fn (string $action) => ['id' => $action, 'label' => self::ACTION_LABELS[$action]],
            self::ACTIONS,
        );
    }

    /** Mapa vista => todas sus acciones válidas (lo que recibe el superadmin). */
    public static function all(): array
    {
        return self::VIEW_ACTIONS;
    }

    /**
     * Normaliza un mapa vista => acciones: descarta vistas/acciones desconocidas y
     * aplica el invariante "sin `ver` no hay crear/editar/eliminar".
     *
     * @param  array<string, list<string>>  $map
     * @return array<string, list<string>>
     */
    public static function normalize(array $map): array
    {
        $normalized = [];

        foreach ($map as $view => $actions) {
            if (! self::isValidView($view) || ! is_array($actions)) {
                continue;
            }

            $valid = array_values(array_intersect($actions, self::VIEW_ACTIONS[$view]));

            if (! in_array('ver', $valid, true)) {
                $valid = array_values(array_intersect($valid, [])); // sin `ver`, nada más se sostiene
            }

            if ($valid !== []) {
                sort($valid);
                $normalized[$view] = $valid;
            }
        }

        ksort($normalized);

        return $normalized;
    }
}
