<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RN-04: la cotizacion congela el tramo que aplico. Sin este dato, cuando
     * el distribuidor edita sus tramos no se puede explicar por que el precio
     * guardado difiere del precio base actual.
     */
    public function up(): void
    {
        Schema::table('cotizaciones_b2b', function (Blueprint $table) {
            $table->unsignedInteger('tramo_cantidad_minima')
                ->nullable()
                ->after('unidades_por_bulto');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones_b2b', function (Blueprint $table) {
            $table->dropColumn('tramo_cantidad_minima');
        });
    }
};
