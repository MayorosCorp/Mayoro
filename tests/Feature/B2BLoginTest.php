<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class B2BLoginTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol = 'bodega'): User
    {
        return User::create([
            'ruc_empresa' => $rol === 'bodega' ? '10456789012' : '20601234567',
            'razon_social' => 'Comercio de Prueba E.I.R.L.',
            'email_contacto' => 'contacto@comercio.pe',
            'telefono_whatsapp' => '+51 999 888 777',
            'rol' => $rol,
            'password' => bcrypt('secreto123'),
        ]);
    }

    /**
     * HU-01: Camino feliz, el acceso se realiza contra la columna email_contacto.
     */

    // php artisan test --filter=test_inicia_sesion_con_el_correo_de_contacto_registrado
    public function test_inicia_sesion_con_el_correo_de_contacto_registrado(): void
    {
        $usuario = $this->usuario();

        $response = $this->post(route('auth.login.post'), [
            'email' => 'contacto@comercio.pe',
            'password' => 'secreto123',
        ]);

        $response->assertRedirect(route('dashboard.index'));
        $this->assertAuthenticatedAs($usuario);
    }

    /**
     * HU-01: Regresión — usuarios_b2b no tiene columna `email`. Con contraseña
     * incorrecta el login debe mostrar el mensaje de validación, nunca lanzar
     * un error SQL de columna desconocida.
     */

    // php artisan test --filter=test_muestra_error_de_validacion_y_no_sql_error_con_contrasena_incorrecta
    public function test_muestra_error_de_validacion_y_no_sql_error_con_contrasena_incorrecta(): void
    {
        $this->usuario();

        $response = $this->post(route('auth.login.post'), [
            'email' => 'contacto@comercio.pe',
            'password' => 'clave-equivocada',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * HU-01: Regresión — un correo inexistente tampoco debe consultar la columna
     * `email`, que no existe en el esquema B2B.
     */

    // php artisan test --filter=test_rechaza_un_correo_no_registrado_sin_error_de_base_de_datos
    public function test_rechaza_un_correo_no_registrado_sin_error_de_base_de_datos(): void
    {
        $response = $this->post(route('auth.login.post'), [
            'email' => 'nadie@existe.pe',
            'password' => 'secreto123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * HU-01: La casilla de recordar sesión se respeta.
     */

    // php artisan test --filter=test_respeta_la_opcion_de_recordar_sesion
    public function test_respeta_la_opcion_de_recordar_sesion(): void
    {
        $usuario = $this->usuario();

        $this->post(route('auth.login.post'), [
            'email' => 'contacto@comercio.pe',
            'password' => 'secreto123',
            'remember' => 'on',
        ]);

        $this->assertAuthenticatedAs($usuario);
        $this->assertNotNull($usuario->fresh()->remember_token);
    }
}
