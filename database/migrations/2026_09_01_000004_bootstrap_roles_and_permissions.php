<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Siembra los 9 roles base y su matriz de permisos (vista × ver/crear/editar/eliminar),
 * derivada de Matriz_Pendientes_Intranet.md. Va en una MIGRACIÓN (no en un seeder) porque
 * RefreshDatabase (usado por todos los tests Feature) solo corre migraciones: sin esto,
 * cualquier test que dependa de un rol fallaría en un entorno recién migrado.
 *
 * Deliberadamente hardcodeada (no lee App\Support\Permissions::VIEWS): una migración es
 * un registro histórico fijo, no debe cambiar de comportamiento si el catálogo de vistas
 * cambia más adelante.
 *
 * `configuraciones` solo lo tiene el superadmin (vía Gate::before, sin filas propias);
 * el resto de roles lo gana más tarde desde la propia UI de Configuraciones.
 */
return new class extends Migration
{
    private const VCED = ['ver', 'crear', 'editar', 'eliminar'];

    private const V = ['ver'];

    private const VC = ['ver', 'crear'];

    private const VE = ['ver', 'editar'];

    public function up(): void
    {
        $roles = [
            ['slug' => 'superadmin', 'name' => 'Superadministrador', 'description' => 'Acceso total; no se puede eliminar ni editar su matriz.', 'protected' => true, 'is_default' => false, 'position' => 0],
            ['slug' => 'gerencia', 'name' => 'Gerencia', 'description' => null, 'protected' => false, 'is_default' => false, 'position' => 10],
            ['slug' => 'directores', 'name' => 'Directores', 'description' => null, 'protected' => false, 'is_default' => false, 'position' => 20],
            ['slug' => 'lideres', 'name' => 'Líderes', 'description' => null, 'protected' => false, 'is_default' => false, 'position' => 30],
            ['slug' => 'rrhh', 'name' => 'RRHH', 'description' => null, 'protected' => false, 'is_default' => false, 'position' => 40],
            ['slug' => 'sig-sst', 'name' => 'SIG/SST', 'description' => null, 'protected' => false, 'is_default' => false, 'position' => 50],
            ['slug' => 'marketing', 'name' => 'Marketing', 'description' => null, 'protected' => false, 'is_default' => false, 'position' => 60],
            ['slug' => 'asistente-gerencia', 'name' => 'Asistente Gerencia', 'description' => null, 'protected' => false, 'is_default' => false, 'position' => 70],
            ['slug' => 'cualquiera', 'name' => 'Cualquiera', 'description' => 'Se aplica automáticamente a todos los usuarios.', 'protected' => true, 'is_default' => true, 'position' => 999],
        ];

        $now = now();
        foreach ($roles as &$role) {
            $role['created_at'] = $now;
            $role['updated_at'] = $now;
        }
        unset($role);

        DB::table('roles')->insert($roles);

        $roleIds = DB::table('roles')->pluck('id', 'slug');

        // Base común a todo grupo administrador (Grupo 1/2/3/4): puede editar contenidos
        // de todas las secciones de consulta general.
        $gestionBase = [
            'inicio' => self::V,
            'enterate' => self::VCED,
            'directorio' => self::V,
            'calendario' => self::VCED,
            'salas' => self::VC,
            'sumate' => self::V,
            'rh' => self::V,
            'sst' => self::V,
            'sig' => self::V,
            'sintyc' => self::V,
            'usuarios' => ['ver', 'crear', 'editar'],
        ];

        $matrix = [
            'cualquiera' => [
                'inicio' => self::V, 'enterate' => self::V, 'directorio' => self::V,
                'calendario' => self::V, 'salas' => self::VC, 'sumate' => self::V,
                'rh' => self::V, 'sst' => self::V, 'sig' => self::V, 'sintyc' => self::V,
            ],
            'gerencia' => $gestionBase,
            'directores' => $gestionBase,
            'lideres' => $gestionBase,
            'rrhh' => array_merge($gestionBase, ['rh' => self::VCED]),
            'sig-sst' => array_merge($gestionBase, [
                'sumate' => self::VE, 'sst' => self::VCED, 'sig' => self::VCED,
            ]),
            'marketing' => array_diff_key($gestionBase, ['usuarios' => null]),
            'asistente-gerencia' => array_merge(
                array_diff_key($gestionBase, ['usuarios' => null]),
                ['enterate' => self::V, 'directorio' => self::VCED],
            ),
            // 'superadmin' → sin filas: Gate::before en AppServiceProvider le da todo.
        ];

        $rows = [];
        foreach ($matrix as $slug => $views) {
            foreach ($views as $view => $actions) {
                foreach ($actions as $action) {
                    $rows[] = ['role_id' => $roleIds[$slug], 'view' => $view, 'action' => $action];
                }
            }
        }

        DB::table('role_permissions')->insert($rows);

        // Todo usuario role_type=admin ya existente pasa a superadmin (compatibilidad).
        $superadminId = $roleIds['superadmin'];
        $adminUserIds = DB::table('users')->where('role_type', 'admin')->pluck('id');

        DB::table('role_user')->insert(
            $adminUserIds->map(fn ($userId) => [
                'role_id' => $superadminId,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
        );
    }

    public function down(): void
    {
        DB::table('role_user')->delete();
        DB::table('role_permissions')->delete();
        DB::table('roles')->delete();
    }
};
