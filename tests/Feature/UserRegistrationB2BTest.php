<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegistrationB2BTest extends TestCase
{
    use RefreshDatabase;

    // php artisan test --filter=test_registro_exitoso_de_nuevo_comercio_b2b_camino_feliz
    public function test_registro_exitoso_de_nuevo_comercio_b2b_camino_feliz(): void
    {
        // HU-01 — Given: un comerciante nuevo con datos válidos
        $data = [
            'ruc_empresa' => '20601234567',
            'razon_social' => 'Distribuidora Mayorista Los Andes S.A.C.',
            'email_contacto' => 'ventas@losandes.pe',
            'telefono_whatsapp' => '987654321',
            'direccion' => 'Av. Los Próceres 450, Cusco',
            'rol' => 'distribuidor',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // When: envía el formulario de registro
        $response = $this->post(route('auth.register.post'), $data);

        // Then: registra la entidad, antepone +51 al teléfono y redirige al dashboard
        $response->assertRedirect(route('dashboard.index'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('usuarios_b2b', [
            'ruc_empresa' => '20601234567',
            'razon_social' => 'Distribuidora Mayorista Los Andes S.A.C.',
            'email_contacto' => 'ventas@losandes.pe',
            'telefono_whatsapp' => '+51987654321',
            'rol' => 'distribuidor',
        ]);
    }

    // php artisan test --filter=test_rechaza_registro_si_ruc_no_tiene_11_digitos_caso_de_borde
    public function test_rechaza_registro_si_ruc_no_tiene_11_digitos_caso_de_borde(): void
    {
        // HU-01 — Given: intento de registro con RUC de 10 dígitos
        $data = [
            'ruc_empresa' => '1023456789', // 10 dígitos (inválido)
            'razon_social' => 'Bodega El Paso',
            'email_contacto' => 'contacto@elpaso.pe',
            'telefono_whatsapp' => '987654321',
            'rol' => 'bodega',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // When: envía el formulario
        $response = $this->post(route('auth.register.post'), $data);

        // Then: rechaza la operación y muestra mensaje de formato inválido
        $response->assertSessionHasErrors([
            'ruc_empresa' => 'Formato de RUC inválido',
        ]);
        $this->assertDatabaseMissing('usuarios_b2b', [
            'email_contacto' => 'contacto@elpaso.pe',
        ]);
    }

    // php artisan test --filter=test_rechaza_registro_por_ruc_o_correo_duplicado
    public function test_rechaza_registro_por_ruc_o_correo_duplicado(): void
    {
        // HU-01 — Given: ya existe un usuario registrado en la base de datos
        User::create([
            'ruc_empresa' => '20601234567',
            'razon_social' => 'Empresa Existente S.A.',
            'email_contacto' => 'duplicado@empresa.pe',
            'telefono_whatsapp' => '+51999888777',
            'rol' => 'distribuidor',
            'password' => bcrypt('password123'),
        ]);

        // When: otro usuario intenta registrarse con ese mismo RUC
        $data = [
            'ruc_empresa' => '20601234567', // duplicado
            'razon_social' => 'Otra Empresa S.A.',
            'email_contacto' => 'nuevo@empresa.pe',
            'telefono_whatsapp' => '912345678',
            'rol' => 'bodega',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('auth.register.post'), $data);

        // Then: rechaza y muestra mensaje de error de duplicidad
        $response->assertSessionHasErrors([
            'ruc_empresa' => 'El RUC o correo electrónico ingresado ya se encuentra registrado',
        ]);
    }

    // php artisan test --filter=test_sanitiza_campos_de_texto_contra_xss
    public function test_sanitiza_campos_de_texto_contra_xss(): void
    {
        // HU-01 — Given: comerciante ingresa etiquetas HTML maliciosas en razón social
        $data = [
            'ruc_empresa' => '10456789012',
            'razon_social' => '<script>alert("XSS")</script>Bodega San Pedro',
            'email_contacto' => 'sanpedro@bodega.pe',
            'telefono_whatsapp' => '988776655',
            'direccion' => '<b>Av. Sol 123</b>',
            'rol' => 'bodega',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // When: procesa el registro
        $response = $this->post(route('auth.register.post'), $data);

        // Then: guarda el texto sanitizado sin etiquetas <script> ni <b>
        $response->assertRedirect(route('dashboard.index'));
        $this->assertDatabaseHas('usuarios_b2b', [
            'ruc_empresa' => '10456789012',
            'razon_social' => 'alert("XSS")Bodega San Pedro',
            'direccion' => 'Av. Sol 123',
        ]);
    }
}
