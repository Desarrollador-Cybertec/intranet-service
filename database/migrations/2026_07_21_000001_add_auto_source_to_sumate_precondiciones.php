<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-condiciones que el sistema puede calcular solo, en vez de que Gestión
 * Humana las marque a mano: la antigüedad sale de users.joined_at y las
 * capacitaciones de course_enrollments. Ver App\Services\SumateService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sumate_precondiciones', function (Blueprint $table) {
            $table->string('auto_source')->nullable()->after('desc');
        });
    }

    public function down(): void
    {
        Schema::table('sumate_precondiciones', function (Blueprint $table) {
            $table->dropColumn('auto_source');
        });
    }
};
