<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ficha CI-BD-08: HU-03 necesita distinguir el estado de stock del producto
     * para etiquetarlo como "Temporalmente sin stock" en el catálogo público.
     */
    public function up(): void
    {
        Schema::table('productos_mayoristas', function (Blueprint $table) {
            $table->unsignedInteger('stock_disponible')->default(0)->after('precio_unitario_sugerido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos_mayoristas', function (Blueprint $table) {
            $table->dropColumn('stock_disponible');
        });
    }
};
