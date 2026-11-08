<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ficha CI-BD-09: la cotizacion debe conservar el precio y el contenido del
     * bulto vigentes en el momento de generarla. Calcular los totales con el
     * precio unitario derivado producia importes 12 veces menores en un bulto
     * de 12 unidades y, ademas, dejaba la cotizacion a merced de los cambios
     * posteriores del catalogo.
     */
    public function up(): void
    {
        Schema::table('cotizaciones_b2b', function (Blueprint $table) {
            $table->decimal('precio_bulto', 10, 2)->default(0)->after('precio_unitario');
            $table->unsignedInteger('unidades_por_bulto')->default(1)->after('precio_bulto');
        });

        // Las cotizaciones historicas se rehidratan desde su producto cuando
        // todavia existe. Las que perdieron el producto conservan el precio
        // unitario que ya tenian; no se inventa un precio de bulto.
        DB::table('cotizaciones_b2b')
            ->orderBy('id')
            ->each(function (object $cotizacion): void {
                $producto = DB::table('productos_mayoristas')
                    ->where('id', $cotizacion->producto_id)
                    ->first();

                if ($producto === null) {
                    return;
                }

                DB::table('cotizaciones_b2b')
                    ->where('id', $cotizacion->id)
                    ->update([
                        'precio_bulto' => $producto->precio_bulto,
                        'unidades_por_bulto' => $producto->unidades_por_bulto,
                    ]);
            });

        // "enviada" afirmaba que el mensaje habia llegado al distribuidor, algo
        // que Mayoro no puede saber: solo genero un enlace de WhatsApp. El
        // estado honesto es "generada".
        DB::table('cotizaciones_b2b')
            ->where('estado', 'enviada')
            ->update(['estado' => 'generada']);
    }

    public function down(): void
    {
        DB::table('cotizaciones_b2b')
            ->where('estado', 'generada')
            ->update(['estado' => 'enviada']);

        Schema::table('cotizaciones_b2b', function (Blueprint $table) {
            $table->dropColumn(['precio_bulto', 'unidades_por_bulto']);
        });
    }
};
