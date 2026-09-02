<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue "pendiente de activación" (active=false, activated_at=null) de
 * "desactivada por un admin" (active=false, activated_at con fecha): sin esto,
 * login() no puede dar un mensaje distinto para cada caso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable()->after('active');
        });

        // Todos los usuarios existentes ya estaban operando: se consideran activados hoy.
        DB::table('users')->whereNull('activated_at')->update(['activated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activated_at');
        });
    }
};
