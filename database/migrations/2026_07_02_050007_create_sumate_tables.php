<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sumate_configs', function (Blueprint $table) {
            $table->id();
            $table->string('trimestre');      // p. ej. 'Q3 2026'
            $table->string('periodo_label');  // 'Julio – Septiembre 2026'
            $table->string('cierre_label');   // '30 sep 2026'
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('sumate_precondiciones', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('label');
            $table->string('req');
            $table->text('desc');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique('slug');
        });

        Schema::create('sumate_acciones', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('label');
            $table->string('icon');
            $table->text('desc');
            $table->unsignedInteger('pts_each');
            $table->unsignedInteger('max');
            $table->unsignedInteger('max_pts');
            $table->string('color');
            $table->string('bg');
            $table->string('rango');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique('slug');
        });

        Schema::create('sumate_niveles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('nivel');
            $table->string('emoji');
            $table->string('label');
            $table->unsignedInteger('min');
            $table->unsignedInteger('max');
            $table->string('color');
            $table->string('bg');
            $table->text('beneficio');
            $table->text('condicion');
            $table->timestamps();
        });

        Schema::create('sumate_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('initials', 4);
            $table->string('color');
            $table->string('area');
            $table->timestamps();
        });

        // Estado de cada precondición por participante (validado server-side).
        Schema::create('sumate_precondition_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('sumate_participants')->cascadeOnDelete();
            $table->foreignId('precondicion_id')->constrained('sumate_precondiciones')->cascadeOnDelete();
            $table->boolean('value')->default(false);
            $table->timestamps();

            $table->unique(['participant_id', 'precondicion_id'], 'sumate_precond_status_unique');
        });

        // Conteo de cada acción por participante (respeta max al registrar).
        Schema::create('sumate_action_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('sumate_participants')->cascadeOnDelete();
            $table->foreignId('accion_id')->constrained('sumate_acciones')->cascadeOnDelete();
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['participant_id', 'accion_id'], 'sumate_action_count_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sumate_action_counts');
        Schema::dropIfExists('sumate_precondition_statuses');
        Schema::dropIfExists('sumate_participants');
        Schema::dropIfExists('sumate_niveles');
        Schema::dropIfExists('sumate_acciones');
        Schema::dropIfExists('sumate_precondiciones');
        Schema::dropIfExists('sumate_configs');
    }
};
