<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * RN-03: vitrina publica del Empresario. El slug identifica la pagina
     * `/empresas/{slug}` y el resto de campos permite personalizar el branding
     * sin tocar el catalogo. El backfill garantiza que los Empresarios ya
     * registrados tengan una direccion publica antes de exigir unicidad.
     */
    public function up(): void
    {
        Schema::table('usuarios_b2b', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->after('razon_social');
            $table->text('descripcion')->nullable()->after('rubro');
            $table->string('color_primario', 7)->default('#23497b')->after('descripcion');
            $table->string('horario_atencion', 120)->nullable()->after('color_primario');
        });

        $this->completarSlugsDeEmpresarios();

        Schema::table('usuarios_b2b', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios_b2b', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'descripcion', 'color_primario', 'horario_atencion']);
        });
    }

    /**
     * Genera un slug legible y unico para cada Empresario existente. Se
     * consulta la tabla en cada intento para no chocar con otro negocio que
     * tenga la misma razon social.
     */
    private function completarSlugsDeEmpresarios(): void
    {
        $empresarios = DB::table('usuarios_b2b')
            ->where('rol', 'empresario')
            ->whereNull('slug')
            ->orderBy('id')
            ->get(['id', 'razon_social']);

        foreach ($empresarios as $empresario) {
            $base = Str::slug((string) $empresario->razon_social);
            $base = rtrim(mb_substr($base !== '' ? $base : 'empresa', 0, 70), '-');
            $base = $base !== '' ? $base : 'empresa';

            $slug = $base;
            $contador = 2;

            while (DB::table('usuarios_b2b')->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$contador;
                $contador++;
            }

            DB::table('usuarios_b2b')
                ->where('id', $empresario->id)
                ->update(['slug' => $slug]);
        }
    }
};
