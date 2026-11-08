<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegistrationB2BTest extends TestCase
{
    use RefreshDatabase;

    // php artisan test --filter=test_registro_sin_ruc_crea_persona_natural_y_redirige_al_dashboard
    public function test_registro_sin_ruc_crea_persona_natural_y_redirige_al_dashboard(): void
    {
        // RN-01 — Given: un visitante con datos basicos y sin RUC
        $data = [
            'razon_social' => 'Maria Fernanda Quispe',
            'email_contacto' => 'maria@correo.pe',
            'telefono_whatsapp' => '987654321',
            'direccion' => 'Av. Los Proceres 450, Cusco',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // When: envia el formulario de registro
        $response = $this->post(route('auth.register.post'), $data);

        // Then: se crea una Persona natural sin RUC y entra al dashboard
        $response->assertRedirect(route('dashboard.index'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('usuarios_b2b', [
            'ruc_empresa' => null,
            'razon_social' => 'Maria Fernanda Quispe',
            'email_contacto' => 'maria@correo.pe',
            'telefono_whatsapp' => '+51987654321',
            'rol' => User::ROL_PERSONA_NATURAL,
        ]);
    }

    // php artisan test --filter=test_registro_ignora_rol_y_ruc_enviados_por_el_cliente
    public function test_registro_ignora_rol_y_ruc_enviados_por_el_cliente(): void
    {
        // RN-01 / RN-02 — Given: un visitante intenta autoasignarse el rol Empresario
        $data = [
            'razon_social' => 'Intento de Escalada',
            'email_contacto' => 'escalada@correo.pe',
            'telefono_whatsapp' => '912345678',
            'ruc_empresa' => '20123456786',
            'rol' => User::ROL_EMPRESARIO,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // When: envia el formulario manipulado
        $this->post(route('auth.register.post'), $data);

        // Then: el servidor lo registra como Persona natural y no guarda el RUC
        $this->assertDatabaseHas('usuarios_b2b', [
            'email_contacto' => 'escalada@correo.pe',
            'ruc_empresa' => null,
            'rol' => User::ROL_PERSONA_NATURAL,
        ]);
    }

    // php artisan test --filter=test_registro_rechaza_correo_duplicado
    public function test_registro_rechaza_correo_duplicado(): void
    {
        // Given: ya existe una cuenta con el correo solicitado
        User::create([
            'ruc_empresa' => null,
            'razon_social' => 'Cuenta Existente',
            'email_contacto' => 'duplicado@empresa.pe',
            'telefono_whatsapp' => '+51999888777',
            'rol' => User::ROL_PERSONA_NATURAL,
            'password' => bcrypt('password123'),
        ]);

        // When: otra persona intenta registrarse con el mismo correo
        $response = $this->post(route('auth.register.post'), [
            'razon_social' => 'Otra Persona',
            'email_contacto' => 'duplicado@empresa.pe',
            'telefono_whatsapp' => '912345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Then: se rechaza el registro y no se duplica la cuenta
        $response->assertSessionHasErrors('email_contacto');
        $this->assertDatabaseCount('usuarios_b2b', 1);
    }

    // php artisan test --filter=test_registro_rechaza_telefono_que_no_es_movil_peruano
    public function test_registro_rechaza_telefono_que_no_es_movil_peruano(): void
    {
        // Given: un telefono que no cumple el formato movil peruano
        $data = [
            'razon_social' => 'Persona Sin Telefono Valido',
            'email_contacto' => 'sintelefono@correo.pe',
            'telefono_whatsapp' => '12345',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // When: envia el formulario
        $response = $this->post(route('auth.register.post'), $data);

        // Then: se rechaza el telefono y no se crea la cuenta
        $response->assertSessionHasErrors('telefono_whatsapp');
        $this->assertDatabaseMissing('usuarios_b2b', [
            'email_contacto' => 'sintelefono@correo.pe',
        ]);
    }

    // php artisan test --filter=test_registro_sanitiza_nombre_y_direccion_contra_xss
    public function test_registro_sanitiza_nombre_y_direccion_contra_xss(): void
    {
        // Given: una Persona natural ingresa etiquetas HTML maliciosas
        $data = [
            'razon_social' => '<script>alert("XSS")</script>Bodega San Pedro',
            'email_contacto' => 'sanpedro@bodega.pe',
            'telefono_whatsapp' => '988776655',
            'direccion' => '<b>Av. Sol 123</b>',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // When: procesa el registro
        $response = $this->post(route('auth.register.post'), $data);

        // Then: guarda el texto saneado, sin etiquetas <script> ni <b>
        $response->assertRedirect(route('dashboard.index'));
        $this->assertDatabaseHas('usuarios_b2b', [
            'email_contacto' => 'sanpedro@bodega.pe',
            'razon_social' => 'alert("XSS")Bodega San Pedro',
            'direccion' => 'Av. Sol 123',
            'rol' => User::ROL_PERSONA_NATURAL,
        ]);
    }
}
