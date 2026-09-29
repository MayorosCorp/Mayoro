<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ficha CI-BD-07: Creación de la tabla cotizaciones_b2b para HU-04.
     */
    public function up(): void
    {
        Schema::create('cotizaciones_b2b', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                ->constrained('productos_mayoristas')
                ->cascadeOnDelete();
            $table->foreignId('bodega_id')
                ->constrained('usuarios_b2b')
                ->cascadeOnDelete();
            $table->foreignId('distribuidor_id')
                ->constrained('usuarios_b2b')
                ->cascadeOnDelete();
            $table->unsignedInteger('cantidad_solicitada'); // Bultos pedidos por la bodega
            $table->decimal('precio_unitario', 10, 2); // Snapshot del precio referencial
            $table->decimal('subtotal', 10, 2);
            $table->decimal('igv', 10, 2); // 18% del subtotal
            $table->decimal('total', 10, 2);
            $table->string('telefono_destino', 32); // E.164 del distribuidor
            $table->text('url_whatsapp')->nullable();
            $table->string('estado')->default('pendiente'); // pendiente | enviada | aceptada | rechazada
            $table->timestamps();

            $table->index(['distribuidor_id', 'estado']);
            $table->index('bodega_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizaciones_b2b');
    }
};
