<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * `slug` estable para que el constructor de módulos (F4) pueda referenciar un curso
 * concreto (p. ej. la toggle de "reinducción SST") sin depender de su `id` numérico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('label');
        });

        DB::table('courses')->select('id', 'label')->orderBy('id')->each(function ($course) {
            DB::table('courses')->where('id', $course->id)->update(['slug' => Str::slug($course->label)]);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
