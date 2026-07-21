<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto de perfil del colaborador. La usa el Directorio como `image`; si queda
 * null, el front pinta las iniciales + color (ver DirectoryPersonResource).
 * Guarda la URL/ruta de la imagen; la subida del archivo es de otra fase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('extension');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
