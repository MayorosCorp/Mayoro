<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ficha CI-BD-09: la convencion exige todo precio decimal(10,2)
     * "estrictamente mayor a cero". La capa HTTP ya valida gt:0, pero el
     * esquema no impediria un INSERT directo con un precio nulo o negativo;
     * se agrega la restriccion CHECK para que la base sea la ultima barrera.
     *
     * SQLite (las pruebas en memoria) no soporta ALTER TABLE ADD CONSTRAINT;
     * la restriccion se omite en ese driver sin perder cobertura, porque la
     * validacion de la FormRequest ya verifica el mismo requisito.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE productos_mayoristas ADD CONSTRAINT chk_precio_bulto_positivo CHECK (precio_bulto > 0)');
        DB::statement('ALTER TABLE productos_mayoristas ADD CONSTRAINT chk_precio_unitario_positivo CHECK (precio_unitario_sugerido > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE productos_mayoristas DROP CONSTRAINT chk_precio_bulto_positivo');
        DB::statement('ALTER TABLE productos_mayoristas DROP CONSTRAINT chk_precio_unitario_positivo');
    }
};
