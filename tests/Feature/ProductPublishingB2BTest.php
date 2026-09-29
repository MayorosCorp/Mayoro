<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPublishingB2BTest extends TestCase
{
    use RefreshDatabase;

    /**
     * HU-02: Camino Feliz - Publicación exitosa por parte de un Distribuidor Mayorista.
     */
    public function test_distribuidor_puede_publicar_producto_con_condiciones_mayoristas_camino_feliz(): void
    {
        $distribuidor = User::create([
            'ruc_empresa' => '20601234567',
            'razon_social' => 'Distribuidora Central S.A.C.',
            'email_contacto' => 'distribuidor@central.pe',
            'telefono_whatsapp' => '+51987654321',
            'direccion' => 'Av. Los Productores 456',
            'rol' => 'distribuidor',
            'password' => bcrypt('password123'),
        ]);

        $payload = [
            'nombre' => 'Aceite Primor 1L x 12',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 120.00,
            'moq_cantidad_minima' => 5,
            'descripcion' => 'Caja sellada de 12 botellas de 1L cada una.',
        ];

        $response = $this->actingAs($distribuidor)->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('productos_mayoristas', [
            'distribuidor_id' => $distribuidor->id,
            'nombre' => 'Aceite Primor 1L x 12',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 120.00,
            'moq_cantidad_minima' => 5,
            'precio_unitario_sugerido' => 10.00,
            'is_active' => true,
        ]);
    }

    /**
     * HU-02: Caso Negativo - Rechazo si el precio o MOQ es menor o igual a cero.
     */
    public function test_rechaza_publicacion_con_precio_o_moq_menor_o_igual_a_cero(): void
    {
        $distribuidor = User::create([
            'ruc_empresa' => '20601234567',
            'razon_social' => 'Distribuidora Central S.A.C.',
            'email_contacto' => 'distribuidor@central.pe',
            'telefono_whatsapp' => '+51987654321',
            'rol' => 'distribuidor',
            'password' => bcrypt('password123'),
        ]);

        $payload = [
            'nombre' => 'Arroz Costeño 50kg',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Saco',
            'unidades_por_bulto' => 1,
            'precio_bulto' => 0.00, // Inválido: <= 0
            'moq_cantidad_minima' => 0, // Inválido: <= 0
        ];

        $response = $this->actingAs($distribuidor)->post(route('products.store'), $payload);

        $response->assertSessionHasErrors(['precio_bulto', 'moq_cantidad_minima']);
        $this->assertDatabaseCount('productos_mayoristas', 0);

        // Verificamos que el mensaje contenga el texto exigido por la rúbrica
        $errors = session('errors')->getBag('default');
        $this->assertEquals('Debe ingresar un valor numérico positivo mayor a cero', $errors->first('precio_bulto'));
        $this->assertEquals('Debe ingresar un valor numérico positivo mayor a cero', $errors->first('moq_cantidad_minima'));
    }

    /**
     * HU-02: Seguridad RBAC - Usuario con rol "bodega" no puede publicar productos (HTTP 403 Forbidden).
     */
    public function test_bloquea_publicacion_a_usuario_con_rol_bodega_con_http_403_rbac(): void
    {
        $bodega = User::create([
            'ruc_empresa' => '10456789012',
            'razon_social' => 'Bodega Don Pepe',
            'email_contacto' => 'donpepe@bodega.pe',
            'telefono_whatsapp' => '+51999888777',
            'rol' => 'bodega',
            'password' => bcrypt('password123'),
        ]);

        $payload = [
            'nombre' => 'Galletas Soda x 24',
            'categoria' => 'Golosinas',
            'presentacion' => 'Display',
            'unidades_por_bulto' => 24,
            'precio_bulto' => 18.50,
            'moq_cantidad_minima' => 3,
        ];

        $response = $this->actingAs($bodega)->post(route('products.store'), $payload);

        $response->assertForbidden();
        $this->assertDatabaseCount('productos_mayoristas', 0);
    }

    /**
     * HU-02: Seguridad Anónima - Usuario no autenticado es redirigido al login.
     */
    public function test_bloquea_publicacion_a_visitante_no_autenticado(): void
    {
        $payload = [
            'nombre' => 'Leche Gloria Caja x 24',
            'categoria' => 'Lácteos',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 24,
            'precio_bulto' => 96.00,
            'moq_cantidad_minima' => 2,
        ];

        $response = $this->post(route('products.store'), $payload);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('productos_mayoristas', 0);
    }

    /**
     * HU-02: Sanitización de campos contra inyección de scripts XSS.
     */
    public function test_sanitiza_campos_de_texto_del_producto_contra_xss(): void
    {
        $distribuidor = User::create([
            'ruc_empresa' => '20601234567',
            'razon_social' => 'Distribuidora Central S.A.C.',
            'email_contacto' => 'distribuidor@central.pe',
            'telefono_whatsapp' => '+51987654321',
            'rol' => 'distribuidor',
            'password' => bcrypt('password123'),
        ]);

        $payload = [
            'nombre' => "<script>alert('hack')</script>Azúcar Rubia 50kg",
            'categoria' => '<b>Abarrotes</b>',
            'presentacion' => '<i>Saco</i>',
            'unidades_por_bulto' => 1,
            'precio_bulto' => 160.00,
            'moq_cantidad_minima' => 2,
            'descripcion' => "<script>window.location='malicious.com'</script>Saco de azúcar pura.",
        ];

        $response = $this->actingAs($distribuidor)->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('productos_mayoristas', [
            'distribuidor_id' => $distribuidor->id,
            'nombre' => 'Azúcar Rubia 50kg',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Saco',
            'descripcion' => 'Saco de azúcar pura.',
        ]);
    }
}
