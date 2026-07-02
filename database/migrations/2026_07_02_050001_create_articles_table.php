<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Feed unificado: noticias, comunicados, reconocimientos y eventos.
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['noticias', 'comunicados', 'reconocimientos', 'eventos']);
            $table->string('tag');
            $table->string('tag_bg');
            $table->string('tag_color');
            $table->string('title');
            $table->text('excerpt');
            $table->string('date'); // texto ya formateado (ver contrato: fechas string)
            $table->string('author');
            $table->json('imgs');
            $table->longText('body'); // HTML
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
