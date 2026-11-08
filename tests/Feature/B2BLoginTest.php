<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class B2BLoginTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol = 'persona_natural'): User
    {
        return User::create([
            'ruc_empresa' => $rol === 'persona_natural' ? '10456789019' : '20601234565',
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
        // HU-01 — Given: un usuario registrado con el correo email_contacto y contraseña válida
        $usuario = $this->usuario();

        // When: envía el formulario de login con ese correo y su contraseña
        $response = $this->post(route('auth.login.post'), [
            'email' => 'contacto@comercio.pe',
            'password' => 'secreto123',
        ]);

        // Then: se redirige al dashboard y la sesión queda autenticada
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
        // HU-01 — Given: un usuario registrado con el correo email_contacto
        $this->usuario();

        // When: envía el formulario de login con una contraseña incorrecta
        $response = $this->post(route('auth.login.post'), [
            'email' => 'contacto@comercio.pe',
            'password' => 'clave-equivocada',
        ]);

        // Then: aparece un error de validación sin SQL y la sesión sigue sin iniciar
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
        // HU-01 — Given: no existe ningún usuario con el correo nadie@existe.pe
        // When: envía el formulario de login con ese correo inexistente
        $response = $this->post(route('auth.login.post'), [
            'email' => 'nadie@existe.pe',
            'password' => 'secreto123',
        ]);

        // Then: aparece un error de validación sin SQL y la sesión sigue sin iniciar
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * HU-01: La casilla de recordar sesión se respeta.
     */

    // php artisan test --filter=test_respeta_la_opcion_de_recordar_sesion
    public function test_respeta_la_opcion_de_recordar_sesion(): void
    {
        // HU-01 — Given: un usuario registrado con credenciales válidas
        $usuario = $this->usuario();

        // When: envía el formulario de login marcando la casilla de recordar sesión
        $this->post(route('auth.login.post'), [
            'email' => 'contacto@comercio.pe',
            'password' => 'secreto123',
            'remember' => 'on',
        ]);

        // Then: la sesión inicia y el usuario queda con remember_token generado
        $this->assertAuthenticatedAs($usuario);
        $this->assertNotNull($usuario->fresh()->remember_token);
    }
}
