<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('title');
            $table->text('description');
            $table->boolean('anonymous')->default(false);
            $table->integer('votes')->default(0);
            $table->string('date');
            // author_id real SIEMPRE persistido (auditoría). author = display público.
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author');
            $table->string('status')->default('pendiente'); // moderación admin
            $table->timestamps();
        });

        Schema::create('idea_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['idea_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idea_votes');
        Schema::dropIfExists('ideas');
    }
};
