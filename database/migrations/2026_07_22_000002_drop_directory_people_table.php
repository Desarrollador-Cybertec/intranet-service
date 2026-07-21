<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El Directorio deja de ser una tabla propia y pasa a ser una proyección de
 * lectura de `users` (ver DirectoryController). Se elimina `directory_people`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('directory_people');
    }

    public function down(): void
    {
        Schema::create('directory_people', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role');
            $table->string('area');
            $table->string('image')->nullable();
            $table->string('initials', 4);
            $table->string('color');
            $table->string('email');
            $table->string('phone');
            $table->timestamps();

            $table->index('area');
        });
    }
};
