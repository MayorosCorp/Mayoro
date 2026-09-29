<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ficha CI-BD-02: Creación de la tabla productos_mayoristas para HU-02.
     */
    public function up(): void
    {
        Schema::create('productos_mayoristas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribuidor_id')
                ->constrained('usuarios_b2b')
                ->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('categoria');
            $table->string('presentacion'); // ej: Caja, Fardo, Display, Saco
            $table->unsignedInteger('unidades_por_bulto');
            $table->decimal('precio_bulto', 10, 2);
            $table->unsignedInteger('moq_cantidad_minima'); // Cantidad mínima de pedido
            $table->decimal('precio_unitario_sugerido', 10, 2);
            $table->string('imagen_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices de búsqueda y optimización
            $table->index(['categoria', 'is_active']);
            $table->index('distribuidor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos_mayoristas');
    }
};
