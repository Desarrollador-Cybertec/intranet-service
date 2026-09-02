<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fila por (rol, vista, acción). `view`/`action` son string, no enum: el catálogo
        // de vistas vive en código (App\Support\Permissions) y crece sin tocar el esquema.
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('view', 32);
            $table->string('action', 16);

            $table->unique(['role_id', 'view', 'action'], 'role_permissions_unique');
            $table->index(['view', 'action']); // guardrail: ¿queda alguien con configuraciones.editar?
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
