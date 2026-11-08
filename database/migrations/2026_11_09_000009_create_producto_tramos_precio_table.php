<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RN-04: tramos de descuento por volumen. Cada tramo define la cantidad
     * minima de bultos, en orden ascendente, y el precio por bulto aplicable
     * desde ese umbral. Los tramos son opcionales y mutuamente excluyentes: la
     * cotizacion toma el de mayor umbral que no supere la cantidad pedida.
     */
    public function up(): void
    {
        Schema::create('producto_tramos_precio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                ->constrained('productos_mayoristas')
                ->cascadeOnDelete();
            $table->unsignedInteger('cantidad_minima');
            $table->decimal('precio_bulto', 10, 2);
            $table->timestamps();

            // Dos tramos con el mismo umbral dejarian el precio a merced del
            // orden de lectura, que es justo la ambiguedad que RN-04 prohibe.
            $table->unique(['producto_id', 'cantidad_minima'], 'tramos_producto_umbral_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_tramos_precio');
    }
};
