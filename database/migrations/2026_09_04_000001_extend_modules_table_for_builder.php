<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Constructor genérico de módulos: `section` deja de ser un enum nativo (ampliarlo
 * exige doctrine/dbal para `->change()`, que este proyecto no tiene instalado, y los
 * tests corren en sqlite) y pasa a validarse en la app vía `Module::SECTIONS`. La
 * columna se reemplaza por add+copy+drop+rename en vez de `change()`, para que
 * funcione igual en MySQL (dev) y sqlite (tests) sin esa dependencia.
 *
 * Se agregan `type` (qué hace la tarjeta), `href`, `config` (json libre por tipo)
 * y `visible` (ocultar sin borrar: conserva lo editado si un slug se retira de
 * un seeder).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->string('section_new', 20)->nullable()->after('id');
        });

        DB::statement('UPDATE modules SET section_new = section');

        Schema::table('modules', function (Blueprint $table) {
            $table->dropUnique(['section', 'slug']);
            $table->dropColumn('section');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->renameColumn('section_new', 'section');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->string('type', 20)->default('enlace')->after('slug');
            $table->string('href', 2048)->nullable()->after('desc');
            $table->json('config')->nullable()->after('href');
            $table->boolean('visible')->default(true)->after('config');

            $table->unique(['section', 'slug']);
            $table->index(['section', 'visible', 'position']);
        });

        DB::table('modules')->update(['type' => 'enlace']);
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropIndex(['section', 'visible', 'position']);
            $table->dropColumn(['type', 'href', 'config', 'visible']);
        });
    }
};
