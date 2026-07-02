<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('icon');
            $table->enum('tag', ['Obligatorio', 'Desarrollo', 'Técnico']);
            $table->string('tag_color');
            $table->string('tag_bg');
            $table->text('desc');
            $table->string('duration');
            $table->enum('modality', ['Virtual', 'Presencial', 'Mixto']);
            $table->timestamps();
        });

        // completed y progress son relativos al usuario autenticado.
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('courses');
    }
};
