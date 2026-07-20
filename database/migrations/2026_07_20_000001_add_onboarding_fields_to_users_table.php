<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding de usuarios importados desde la nómina (users:import).
 * Un usuario importado llega sin cargo/área/teléfono/fecha de ingreso y debe
 * completarlos en el primer inicio de sesión (ver EnsureProfileCompleted).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('profile_completed_at')->nullable()->after('extension');
            // El default 'Colaborador' haría que todo perfil importado pareciera completo.
            $table->string('role')->nullable()->default(null)->change();
        });

        // Los usuarios que ya existían sí tienen perfil: no deben pasar por el onboarding.
        DB::table('users')->update(['profile_completed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_completed_at');
            $table->string('role')->default('Colaborador')->change();
        });
    }
};
