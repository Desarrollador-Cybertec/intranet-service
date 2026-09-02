<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles como dato: se crean/editan desde la intranet (Configuraciones), no en código.
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique(); // inmutable tras crearse
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->boolean('protected')->default(false); // no se puede borrar/renombrar (superadmin, cualquiera)
            $table->boolean('is_default')->default(false); // se aplica implícitamente a todos (cualquiera)
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index('position');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
