<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ficha CI-02 / CI-BD-01: Migración Tabla usuarios_b2b
     */
    public function up(): void
    {
        Schema::create('usuarios_b2b', function (Blueprint $table) {
            $table->id();
            $table->string('ruc_empresa', 11)->unique();
            $table->string('razon_social');
            $table->string('email_contacto')->unique();
            $table->string('telefono_whatsapp', 20);
            $table->string('direccion')->nullable();
            $table->string('password');
            $table->enum('rol', ['bodega', 'distribuidor'])->default('bodega');
            $table->boolean('is_premium')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios_b2b');
    }
};
