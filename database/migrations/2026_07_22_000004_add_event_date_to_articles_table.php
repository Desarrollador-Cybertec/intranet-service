<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha real del evento (solo aplica a type=eventos). El campo `date` sigue siendo
 * el texto de display; `event_date` permite ordenar/contar "eventos próximos".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->date('event_date')->nullable()->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('event_date');
        });
    }
};
