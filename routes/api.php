<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SumateController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Insumma Intranet
|--------------------------------------------------------------------------
| Contrato: .context/mocks/.context/02-contrato-api.md
| 🌐 = público · resto requiere auth:sanctum · escritura de gestión = perm:<vista>,<accion>
| Todo salvo /auth/me y /auth/logout exige perfil completo (428 si falta).
| Calendario y Salas quedan FUERA DE ALCANCE (Nextcloud CalDAV).
|
| Reconocimientos / Foro / Buzón de Ideas / Capacitaciones se eliminaron de la
| intranet (matriz de pendientes): sus modelos, migraciones y datos se
| conservan, pero ya no tienen endpoints ni vista propia.
*/

// ── Auth (público) ────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // ── Auth (autenticado) ────────────────────────────────────────
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/auth/me', [AuthController::class, 'updateMe']);
});

// ── Resto de la app: exige además tener el perfil completo ────────
Route::middleware(['auth:sanctum', 'active', 'profile.completed'])->group(function () {

    // ── Inicio (dashboard): KPIs, cumpleaños y notificaciones ─────
    Route::middleware('perm:inicio')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'summary']);
        Route::get('/dashboard/notifications', [DashboardController::class, 'notifications']);
    });

    // ── Entérate: noticias y comunicados ──────────────────────────
    Route::middleware('perm:enterate')->group(function () {
        Route::get('/news', [ArticleController::class, 'index'])->defaults('type', 'noticias');
        Route::get('/news/{article}', [ArticleController::class, 'show'])->defaults('type', 'noticias');
        Route::get('/comunicados', [ArticleController::class, 'index'])->defaults('type', 'comunicados');

        Route::middleware('perm:enterate,crear')->group(function () {
            Route::post('/news', [ArticleController::class, 'store'])->defaults('type', 'noticias');
            Route::post('/comunicados', [ArticleController::class, 'store'])->defaults('type', 'comunicados');
        });
        Route::middleware('perm:enterate,editar')->group(function () {
            Route::put('/news/{article}', [ArticleController::class, 'update'])->defaults('type', 'noticias');
            Route::put('/comunicados/{article}', [ArticleController::class, 'update'])->defaults('type', 'comunicados');
        });
        Route::middleware('perm:enterate,eliminar')->group(function () {
            Route::delete('/news/{article}', [ArticleController::class, 'destroy'])->defaults('type', 'noticias');
            Route::delete('/comunicados/{article}', [ArticleController::class, 'destroy'])->defaults('type', 'comunicados');
        });
    });

    // ── Calendario: eventos corporativos ───────────────────────────
    Route::middleware('perm:calendario')->group(function () {
        Route::get('/events', [ArticleController::class, 'index'])->defaults('type', 'eventos');
        Route::post('/events/{article}/inscripcion', [EventController::class, 'inscripcion']);

        Route::post('/events', [ArticleController::class, 'store'])->defaults('type', 'eventos')->middleware('perm:calendario,crear');
        Route::put('/events/{article}', [ArticleController::class, 'update'])->defaults('type', 'eventos')->middleware('perm:calendario,editar');
        Route::delete('/events/{article}', [ArticleController::class, 'destroy'])->defaults('type', 'eventos')->middleware('perm:calendario,eliminar');
    });

    // ── Directorio ────────────────────────────────────────────────
    Route::middleware('perm:directorio')->group(function () {
        Route::get('/directory', [DirectoryController::class, 'index']);
        Route::get('/directory/entries', [DirectoryController::class, 'entries']);
        Route::patch('/directory/entries/reorder', [DirectoryController::class, 'reorder'])->middleware('perm:directorio,editar');
        Route::post('/directory/entries/import', [DirectoryController::class, 'import'])->middleware('perm:directorio,crear');
        Route::post('/directory/entries', [DirectoryController::class, 'store'])->middleware('perm:directorio,crear');
        Route::patch('/directory/entries/{directoryPerson}', [DirectoryController::class, 'update'])->middleware('perm:directorio,editar');
        Route::delete('/directory/entries/{directoryPerson}', [DirectoryController::class, 'destroy'])->middleware('perm:directorio,eliminar');
    });

    // ── Súmate (lectura para quien tenga sumate.ver) ───────────────
    Route::get('/sumate/participants', [SumateController::class, 'participants'])->middleware('perm:sumate');
    Route::middleware('perm:sumate,editar')->group(function () {
        Route::post('/sumate/acciones', [SumateController::class, 'registerAction']);
        Route::patch('/sumate/participants/{participant}/precondiciones', [SumateController::class, 'setPreconditions']);
        Route::put('/sumate/config', [SumateController::class, 'updateConfig']);
    });

    // ── Gestión: RH / SST / SIG / SINTYC / Inicio (catálogos de módulos) ──
    foreach (['rh', 'sst', 'sig', 'sintyc', 'inicio'] as $section) {
        Route::middleware("perm:{$section}")->group(function () use ($section) {
            Route::get("/{$section}/modules", [ModuleController::class, 'index'])->defaults('section', $section);
            Route::get("/{$section}/modules/all", [ModuleController::class, 'all'])->defaults('section', $section)->middleware("perm:{$section},editar");
            Route::post("/{$section}/modules", [ModuleController::class, 'store'])->defaults('section', $section)->middleware("perm:{$section},crear");
            Route::patch("/{$section}/modules/reorder", [ModuleController::class, 'reorder'])->defaults('section', $section)->middleware("perm:{$section},editar");
            Route::put("/{$section}/modules/{slug}", [ModuleController::class, 'update'])->defaults('section', $section)->middleware("perm:{$section},editar");
            Route::delete("/{$section}/modules/{slug}", [ModuleController::class, 'destroy'])->defaults('section', $section)->middleware("perm:{$section},eliminar");
        });
    }

    // ── Formularios dinámicos (RH/SST) ─────────────────────────────
    // /forms/submissions/* antes de /forms/{slug} — si no, {slug} captura "submissions".
    Route::get('/forms/submissions/mine', [FormController::class, 'mine']);
    Route::get('/forms/submissions/{submission}', [FormController::class, 'showSubmission']);
    Route::patch('/forms/submissions/{submission}', [FormController::class, 'updateSubmission']);
    Route::get('/forms/submissions/{submission}/attachments/{attachment}', [FormController::class, 'downloadAttachment']);
    Route::get('/forms', [FormController::class, 'index']);
    Route::get('/forms/{slug}', [FormController::class, 'show']);
    Route::post('/forms/{slug}/submissions', [FormController::class, 'store']);
    Route::get('/forms/{slug}/submissions', [FormController::class, 'forSection']);

    // ── Administración de usuarios ──────────────────────────────────
    Route::middleware('perm:usuarios')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store'])->middleware('perm:usuarios,crear');
        Route::patch('/users/{user}', [UserController::class, 'update'])->middleware('perm:usuarios,editar');
        Route::post('/users/{user}/password', [UserController::class, 'resetPassword'])->middleware('perm:usuarios,editar');
        Route::put('/users/{user}/roles', [UserController::class, 'setRoles'])->middleware('perm:usuarios,editar');
        // El catálogo de roles (para el filtro y los chips de asignación) lo consume
        // exclusivamente la UI de Usuarios, no Configuraciones (que usa /permissions/matrix,
        // donde los roles ya vienen incluidos) — por eso vive bajo usuarios.ver, no configuraciones.
        Route::get('/roles', [RoleController::class, 'index']);
    });

    // ── Roles y permisos (Configuraciones) ─────────────────────────
    Route::middleware('perm:configuraciones')->group(function () {
        Route::get('/permissions/catalog', [PermissionController::class, 'catalog']);
        Route::get('/permissions/matrix', [PermissionController::class, 'matrix']);
        Route::put('/permissions/matrix', [PermissionController::class, 'updateMatrix'])->middleware('perm:configuraciones,editar');

        Route::post('/roles', [RoleController::class, 'store'])->middleware('perm:configuraciones,crear');
        Route::get('/roles/{role}', [RoleController::class, 'show']);
        Route::patch('/roles/{role}', [RoleController::class, 'update'])->middleware('perm:configuraciones,editar');
        Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('perm:configuraciones,editar');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('perm:configuraciones,eliminar');
    });
});
