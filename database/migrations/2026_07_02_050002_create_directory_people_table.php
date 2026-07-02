<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        Schema::dropIfExists('directory_people');
    }
};
