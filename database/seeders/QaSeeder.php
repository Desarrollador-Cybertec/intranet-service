<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios y contenido de prueba para verificación manual y E2E (Playwright) contra
 * la API local. Corre PRIMERO en DatabaseSeeder: Forum/Idea/Course/SumateSeeder dependen
 * de que ya existan `user@cybertec.com.co` y `admin@cybertec.com.co`.
 *
 * OJO: la migración `2026_09_01_000004_bootstrap_roles_and_permissions` corre ANTES que
 * este seeder (las migraciones siempre van antes que los seeders), así que su intento de
 * adjuntar `superadmin` a todo `role_type=admin` existente no alcanza a estos usuarios
 * (no existen todavía en ese momento). Por eso cada cuenta admin/grupo de aquí adjunta su
 * rol RBAC explícitamente vía `roleSlugs`.
 */
class QaSeeder extends Seeder
{
    /** Contraseña común de las cuentas @insumma.co (documentada también en docs/API.md). */
    public const QA_PASSWORD = 'Insumma2026!';

    public function run(): void
    {
        $today = Carbon::today();

        // Cuentas fijas que ForumSeeder / IdeaSeeder / CourseSeeder / SumateSeeder
        // esperan encontrar por email y por nombre (ver scripts/seed.php).
        $this->user([
            'name' => 'Usuario Cybertec', 'email' => 'user@cybertec.com.co', 'role_type' => 'user',
            'role' => 'Colaborador', 'area' => 'Comercial', 'phone' => '3000000305', 'extension' => '305',
            'joined_at' => $today->copy()->subYears(3)->subMonths(6),
        ], 'Cybertec2026!');

        $this->user([
            'name' => 'Administrador Cybertec', 'email' => 'admin@cybertec.com.co', 'role_type' => 'admin',
            'role' => 'Administrador', 'area' => 'TI', 'phone' => '3000000100', 'extension' => '100',
            'joined_at' => $today->copy()->subYears(6), 'roleSlugs' => [Role::SUPERADMIN],
        ], 'Cybertec2026!');

        $this->user([
            'name' => 'QA Superadmin', 'email' => 'admin@insumma.co', 'role_type' => 'admin',
            'role' => 'Administrador de la Intranet', 'area' => 'TI', 'phone' => '3000000001',
            'joined_at' => $today->copy()->subYears(4), 'roleSlugs' => [Role::SUPERADMIN],
        ], 'Admin2026#');

        // Colaborador base — representa el rol implícito "Cualquiera".
        $this->user([
            'name' => 'QA Demo', 'email' => 'demo@insumma.co', 'role_type' => 'user',
            'role' => 'Analista Comercial', 'area' => 'Comercial', 'phone' => '3000000002',
            'joined_at' => $today->copy()->subMonths(8),
            'birthday' => $today->copy()->subYears(29), // cumple HOY → widget de Inicio
        ]);

        // Un candidato por grupo de la matriz, ya con su rol RBAC real asignado.
        $grupos = [
            ['email' => 'gerencia@insumma.co',   'name' => 'QA Gerencia',           'role' => 'Gerente General',       'area' => 'Administración',   'roleSlugs' => ['gerencia']],
            ['email' => 'directores@insumma.co', 'name' => 'QA Directores',         'role' => 'Director de Área',      'area' => 'Administración',   'roleSlugs' => ['directores']],
            ['email' => 'lideres@insumma.co',    'name' => 'QA Líder',              'role' => 'Líder de Equipo',       'area' => 'Comercial',         'roleSlugs' => ['lideres']],
            ['email' => 'rrhh@insumma.co',       'name' => 'QA Gestión Humana',     'role' => 'Analista de GH',        'area' => 'Gestión Humana',    'roleSlugs' => ['rrhh']],
            ['email' => 'sigsst@insumma.co',     'name' => 'QA SIG SST',            'role' => 'Coordinador SIG-SST',   'area' => 'TI',                'roleSlugs' => ['sig-sst']],
            ['email' => 'marketing@insumma.co',  'name' => 'QA Marketing',          'role' => 'Analista de Marketing', 'area' => 'Comercial',         'roleSlugs' => ['marketing']],
            ['email' => 'asistente@insumma.co',  'name' => 'QA Asistente Gerencia', 'role' => 'Asistente de Gerencia', 'area' => 'Administración',    'roleSlugs' => ['asistente-gerencia']],
        ];

        // Segundo cumpleaños, mismo mes que "hoy" pero otro día, para el KPI "cumpleañosMes"
        // sin duplicar el caso "hoy" que ya cubre QA Demo.
        $otroDia = $today->day === 1 ? 2 : 1;
        $otroCumple = Carbon::create($today->year - 31, $today->month, $otroDia);

        foreach ($grupos as $i => $g) {
            $this->user($g + [
                'role_type' => 'user',
                'phone' => '30000000'.($i + 10),
                'joined_at' => $today->copy()->subYears(1)->subMonths($i),
                'birthday' => $i === 0 ? $otroCumple : null,
            ]);
        }

        // Onboarding pendiente: perfil incompleto → 428 (importado de nómina).
        $this->user([
            'name' => 'Usuario Importado', 'email' => 'nomina@insumma.co', 'role_type' => 'user',
            'role' => null, 'area' => null, 'phone' => null, 'joined_at' => null,
            'profileCompleted' => false,
        ]);

        // Desactivada POR UN ADMIN (ya había estado activa): activated_at con fecha.
        $this->user([
            'name' => 'Usuario Pendiente', 'email' => 'pendiente@insumma.co', 'role_type' => 'user',
            'role' => 'Analista', 'area' => 'Comercial', 'phone' => '3000000099',
            'joined_at' => $today, 'active' => false, 'activated_at' => $today->copy()->subMonths(2),
        ]);

        // Recién registrada, NUNCA activada (activated_at=null): distingue el mensaje de
        // login "pendiente de activación" del de "cuenta desactivada" de arriba.
        $this->user([
            'name' => 'Usuario Registro Pendiente', 'email' => 'pendiente.activacion@insumma.co', 'role_type' => 'user',
            'role' => 'Analista', 'area' => 'Comercial', 'phone' => '3000000098',
            'joined_at' => $today, 'active' => false, 'activated_at' => null,
        ]);

        $this->seedEvents($today);
    }

    /**
     * @param  array<string,mixed>  $attrs  Acepta los pseudo-campos `profileCompleted` (bool,
     *                                      deja `profile_completed_at` en null) y `roleSlugs`
     *                                      (list<string>, roles RBAC a adjuntar tras guardar).
     */
    private function user(array $attrs, ?string $password = null): User
    {
        $email = $attrs['email'];
        $name = $attrs['name'];
        $profileCompleted = $attrs['profileCompleted'] ?? true;
        $roleSlugs = $attrs['roleSlugs'] ?? [];
        unset($attrs['email'], $attrs['name'], $attrs['profileCompleted'], $attrs['roleSlugs']);

        $defaults = [
            'initials' => User::initialsFrom($name),
            'color' => User::colorFrom($email),
            'active' => true,
            'activated_at' => now(),
            'profile_completed_at' => $profileCompleted ? now() : null,
        ];

        $user = User::updateOrCreate(
            ['email' => $email],
            array_merge($defaults, $attrs, [
                'name' => $name,
                'password' => Hash::make($password ?? self::QA_PASSWORD),
            ]),
        );

        if ($roleSlugs !== []) {
            $user->roles()->syncWithoutDetaching(Role::whereIn('slug', $roleSlugs)->pluck('id'));
        }

        return $user;
    }

    /** Dos eventos con fecha futura para el KPI "eventos próximos" y el calendario. */
    private function seedEvents(Carbon $today): void
    {
        $events = [
            [
                'id' => 9001, 'type' => 'eventos',
                'tag' => 'Institucional', 'tag_bg' => '#E8F5E9', 'tag_color' => '#2E7D32',
                'title' => 'QA — Comité mensual de gerencia',
                'excerpt' => 'Evento de prueba (QaSeeder) para verificar el KPI de eventos próximos.',
                'date' => $today->copy()->addDays(7)->locale('es')->translatedFormat('d M, Y'),
                'event_date' => $today->copy()->addDays(7)->toDateString(),
                'author' => 'QA', 'imgs' => [], 'body' => '<p>Evento de prueba (QaSeeder).</p>',
            ],
            [
                'id' => 9002, 'type' => 'eventos',
                'tag' => 'Social', 'tag_bg' => '#FFF3E0', 'tag_color' => '#E65100',
                'title' => 'QA — Integración de fin de trimestre',
                'excerpt' => 'Segundo evento de prueba (QaSeeder), más lejano en el tiempo.',
                'date' => $today->copy()->addDays(21)->locale('es')->translatedFormat('d M, Y'),
                'event_date' => $today->copy()->addDays(21)->toDateString(),
                'author' => 'QA', 'imgs' => [], 'body' => '<p>Evento de prueba (QaSeeder).</p>',
            ],
        ];

        foreach ($events as $e) {
            Article::updateOrCreate(['id' => $e['id']], $e);
        }
    }
}
