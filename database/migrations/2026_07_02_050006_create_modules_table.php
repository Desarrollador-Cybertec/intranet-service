<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo unificado de módulos de gestión RH / SST / SIG.
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->enum('section', ['rh', 'sst', 'sig']);
            $table->string('slug'); // id estable del contrato (p. ej. 'nomina', 'epp', 'calidad')
            $table->string('label');
            $table->string('icon');
            $table->string('color');
            $table->string('bg');
            $table->text('desc');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['section', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
