<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RN-01 / RN-02: el rol inicial pasa a ser Persona Natural y el RUC deja de
     * ser obligatorio al registrarse. El rol Empresario solo se obtiene tras la
     * validacion fiscal, por eso la columna tambien guarda el rubro comercial.
     *
     * La columna `rol` era un ENUM de MySQL: se ensancha a VARCHAR antes de
     * escribir los valores nuevos, porque MySQL rechaza un valor fuera del ENUM.
     */
    public function up(): void
    {
        Schema::table('usuarios_b2b', function (Blueprint $table) {
            $table->string('rol', 20)->default('persona_natural')->change();
            $table->string('ruc_empresa', 11)->nullable()->change();
            $table->string('rubro', 120)->nullable()->after('razon_social');
        });

        DB::table('usuarios_b2b')->where('rol', 'bodega')->update(['rol' => 'persona_natural']);
        DB::table('usuarios_b2b')->where('rol', 'distribuidor')->update(['rol' => 'empresario']);
    }

    /**
     * Vuelve a los roles anteriores. El RUC se deja nullable para no fallar si
     * existen Personas Naturales registradas sin RUC.
     */
    public function down(): void
    {
        DB::table('usuarios_b2b')->where('rol', 'persona_natural')->update(['rol' => 'bodega']);
        DB::table('usuarios_b2b')->where('rol', 'empresario')->update(['rol' => 'distribuidor']);

        Schema::table('usuarios_b2b', function (Blueprint $table) {
            $table->dropColumn('rubro');
            $table->string('rol', 20)->default('bodega')->change();
        });
    }
};
