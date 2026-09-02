<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * `directory_people` vuelve como tabla propia: el Directorio deja de ser una
 * proyección de `users` porque debe poder listar personas SIN cuenta (D4 de la
 * matriz de pendientes). `user_id` es opcional; cuando existe, DirectoryService
 * mantiene sincronizada la identidad (nombre/foto/iniciales/color) pero NUNCA pisa
 * los campos que un admin curó a mano (área/cargo/teléfono/extensión) si ya tienen
 * un valor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directory_people', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('area')->nullable();
            $table->string('role')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('extension', 10)->nullable();
            $table->string('email')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('photo')->nullable();
            $table->string('initials', 4)->nullable();
            $table->string('color')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('area');
            $table->index(['active', 'position', 'name']);
        });

        // Backfill desde los usuarios activos con perfil completo para que
        // GET /api/directory no quede en blanco al desplegar esta migración.
        $now = now();
        $users = DB::table('users')
            ->where('active', true)
            ->whereNotNull('profile_completed_at')
            ->get(['id', 'name', 'email', 'role', 'area', 'phone', 'extension', 'photo', 'initials', 'color']);

        foreach ($users as $u) {
            DB::table('directory_people')->insert([
                'name' => $u->name,
                'area' => $u->area,
                'role' => $u->role,
                'phone' => $u->phone,
                'extension' => $u->extension,
                'email' => Str::lower($u->email),
                'user_id' => $u->id,
                'photo' => $u->photo,
                'initials' => $u->initials,
                'color' => $u->color,
                'active' => true,
                'position' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_people');
    }
};
