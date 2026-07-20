<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CapacitacionController;
use App\Http\Controllers\Api\DirectoryController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\IdeaController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\SumateController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Insumma Intranet
|--------------------------------------------------------------------------
| Contrato: .context/mocks/.context/02-contrato-api.md
| 🌐 = público · resto requiere auth:sanctum · escritura de gestión = role:admin
| Todo salvo /auth/me y /auth/logout exige perfil completo (428 si falta).
| Calendario y Salas quedan FUERA DE ALCANCE (Nextcloud CalDAV).
*/

// ── Auth (público) ────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // ── Auth (autenticado) ────────────────────────────────────────
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/auth/me', [AuthController::class, 'updateMe']);
});

// ── Resto de la app: exige además tener el perfil completo ────────
Route::middleware(['auth:sanctum', 'active', 'profile.completed'])->group(function () {
    // ── Entérate: noticias y comunicados ──────────────────────────
    Route::get('/news', [ArticleController::class, 'index'])->defaults('type', 'noticias');
    Route::get('/news/{article}', [ArticleController::class, 'show'])->defaults('type', 'noticias');
    Route::get('/comunicados', [ArticleController::class, 'index'])->defaults('type', 'comunicados');
    Route::get('/reconocimientos', [ArticleController::class, 'index'])->defaults('type', 'reconocimientos');
    Route::get('/events', [ArticleController::class, 'index'])->defaults('type', 'eventos');

    // Eventos: inscripción del usuario actual
    Route::post('/events/{article}/inscripcion', [EventController::class, 'inscripcion']);

    // ── Directorio ────────────────────────────────────────────────
    Route::get('/directory', [DirectoryController::class, 'index']);

    // ── Foro ──────────────────────────────────────────────────────
    Route::get('/forum/posts', [ForumController::class, 'index']);
    Route::post('/forum/posts', [ForumController::class, 'store']);
    Route::post('/forum/posts/{forumPost}/vote', [ForumController::class, 'vote']);
    Route::put('/forum/posts/{forumPost}', [ForumController::class, 'update']);   // 👤 autor o admin (Policy)
    Route::delete('/forum/posts/{forumPost}', [ForumController::class, 'destroy']); // 👤 autor o admin (Policy)

    // ── Buzón de Ideas ────────────────────────────────────────────
    Route::get('/ideas', [IdeaController::class, 'index']);
    Route::post('/ideas', [IdeaController::class, 'store']);
    Route::post('/ideas/{idea}/vote', [IdeaController::class, 'vote']);

    // ── Súmate (lectura para todos; otorgar puntos es de admin) ───
    Route::get('/sumate/participants', [SumateController::class, 'participants']);

    // ── Capacitaciones ────────────────────────────────────────────
    Route::get('/capacitaciones', [CapacitacionController::class, 'index']);
    Route::post('/capacitaciones/{course}/inscripcion', [CapacitacionController::class, 'inscripcion']);

    // ── Gestión: RH / SST / SIG (catálogos de módulos) ────────────
    Route::get('/rh/modules', [ModuleController::class, 'index'])->defaults('section', 'rh');
    Route::get('/sst/modules', [ModuleController::class, 'index'])->defaults('section', 'sst');
    Route::get('/sig/modules', [ModuleController::class, 'index'])->defaults('section', 'sig');

    // ── Gestión de contenido / catálogos (admin) ──────────────────
    Route::middleware('role:admin')->group(function () {
        // Administración de usuarios
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::post('/users/{user}/password', [UserController::class, 'resetPassword']);

        // Entérate
        Route::post('/news', [ArticleController::class, 'store'])->defaults('type', 'noticias');
        Route::put('/news/{article}', [ArticleController::class, 'update']);
        Route::delete('/news/{article}', [ArticleController::class, 'destroy']);

        Route::post('/comunicados', [ArticleController::class, 'store'])->defaults('type', 'comunicados');
        Route::put('/comunicados/{article}', [ArticleController::class, 'update']);
        Route::delete('/comunicados/{article}', [ArticleController::class, 'destroy']);

        Route::post('/reconocimientos', [ArticleController::class, 'store'])->defaults('type', 'reconocimientos');
        Route::put('/reconocimientos/{article}', [ArticleController::class, 'update']);
        Route::delete('/reconocimientos/{article}', [ArticleController::class, 'destroy']);

        Route::post('/events', [ArticleController::class, 'store'])->defaults('type', 'eventos');
        Route::put('/events/{article}', [ArticleController::class, 'update']);
        Route::delete('/events/{article}', [ArticleController::class, 'destroy']);

        // Directorio
        Route::post('/directory', [DirectoryController::class, 'store']);
        Route::put('/directory/{directoryPerson}', [DirectoryController::class, 'update']);
        Route::delete('/directory/{directoryPerson}', [DirectoryController::class, 'destroy']);

        // Súmate: el admin otorga acciones y valida pre-condiciones
        Route::post('/sumate/acciones', [SumateController::class, 'registerAction']);
        Route::patch('/sumate/participants/{participant}/precondiciones', [SumateController::class, 'setPreconditions']);
        Route::put('/sumate/config', [SumateController::class, 'updateConfig']);

        // Ideas (moderación / estado)
        Route::patch('/ideas/{idea}', [IdeaController::class, 'update']);
        Route::delete('/ideas/{idea}', [IdeaController::class, 'destroy']);

        // Capacitaciones
        Route::post('/capacitaciones', [CapacitacionController::class, 'store']);
        Route::put('/capacitaciones/{course}', [CapacitacionController::class, 'update']);
        Route::delete('/capacitaciones/{course}', [CapacitacionController::class, 'destroy']);

        // Módulos RH / SST / SIG
        foreach (['rh', 'sst', 'sig'] as $section) {
            Route::post("/{$section}/modules", [ModuleController::class, 'store'])->defaults('section', $section);
            Route::put("/{$section}/modules/{module}", [ModuleController::class, 'update'])->defaults('section', $section);
            Route::delete("/{$section}/modules/{module}", [ModuleController::class, 'destroy'])->defaults('section', $section);
        }
    });
});
