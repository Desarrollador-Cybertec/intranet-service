<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('tag');
            $table->json('tags');
            $table->integer('votes')->default(0);
            $table->string('author'); // nombre display del autor
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('date');
            $table->integer('replies')->default(0);
            $table->timestamps();
        });

        // Voto por usuario: evita doble conteo y permite recalcular el total autoritativo.
        Schema::create('forum_post_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('direction', ['up', 'down']);
            $table->timestamps();

            $table->unique(['forum_post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_post_votes');
        Schema::dropIfExists('forum_posts');
    }
};
