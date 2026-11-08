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

    // php artisan test --filter=test_distribuidor_puede_publicar_producto_con_condiciones_mayoristas_camino_feliz
    public function test_distribuidor_puede_publicar_producto_con_condiciones_mayoristas_camino_feliz(): void
    {
        // HU-02 — Given: un distribuidor mayorista registrado con datos válidos
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
            'stock_disponible' => 240,
            'descripcion' => 'Caja sellada de 12 botellas de 1L cada una.',
        ];

        // When: publica un producto con condiciones mayoristas válidas
        $response = $this->actingAs($distribuidor)->post(route('products.store'), $payload);

        // Then: el producto queda activo en el catálogo y se redirige al listado con mensaje de éxito
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
            'stock_disponible' => 240,
            'precio_unitario_sugerido' => 10.00,
            'is_active' => true,
        ]);
    }

    /**
     * HU-02: Caso Negativo - Rechazo si el precio o MOQ es menor o igual a cero.
     */

    // php artisan test --filter=test_rechaza_publicacion_con_precio_o_moq_menor_o_igual_a_cero
    public function test_rechaza_publicacion_con_precio_o_moq_menor_o_igual_a_cero(): void
    {
        // HU-02 — Given: un distribuidor mayorista registrado y un payload con precio y MOQ en cero
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
            'stock_disponible' => 100,
        ];

        // When: intenta publicar el producto con esos valores inválidos
        $response = $this->actingAs($distribuidor)->post(route('products.store'), $payload);

        // Then: se muestran errores de validación en precio y MOQ y no se persiste ningún producto
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

    // php artisan test --filter=test_bloquea_publicacion_a_usuario_con_rol_bodega_con_http_403_rbac
    public function test_bloquea_publicacion_a_usuario_con_rol_bodega_con_http_403_rbac(): void
    {
        // HU-02 — Given: un usuario autenticado con rol "bodega" (no distribuidor)
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
            'stock_disponible' => 48,
        ];

        // When: intenta publicar un producto válido
        $response = $this->actingAs($bodega)->post(route('products.store'), $payload);

        // Then: el servidor responde HTTP 403 y no se crea ningún producto
        $response->assertForbidden();
        $this->assertDatabaseCount('productos_mayoristas', 0);
    }

    /**
     * HU-02: Seguridad Anónima - Usuario no autenticado es redirigido al login.
     */

    // php artisan test --filter=test_bloquea_publicacion_a_visitante_no_autenticado
    public function test_bloquea_publicacion_a_visitante_no_autenticado(): void
    {
        // HU-02 — Given: un visitante no autenticado con un payload de producto válido
        $payload = [
            'nombre' => 'Leche Gloria Caja x 24',
            'categoria' => 'Lácteos',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 24,
            'precio_bulto' => 96.00,
            'moq_cantidad_minima' => 2,
            'stock_disponible' => 60,
        ];

        // When: intenta publicar el producto sin iniciar sesión
        $response = $this->post(route('products.store'), $payload);

        // Then: es redirigido al login y no se crea ningún producto
        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('productos_mayoristas', 0);
    }

    /**
     * HU-02: Sanitización de campos contra inyección de scripts XSS.
     */

    // php artisan test --filter=test_sanitiza_campos_de_texto_del_producto_contra_xss
    public function test_sanitiza_campos_de_texto_del_producto_contra_xss(): void
    {
        // HU-02 — Given: un distribuidor mayorista registrado y un payload con etiquetas HTML maliciosas
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
            'stock_disponible' => 75,
            'descripcion' => "<script>window.location='malicious.com'</script>Saco de azúcar pura.",
        ];

        // When: publica el producto con esos campos de texto
        $response = $this->actingAs($distribuidor)->post(route('products.store'), $payload);

        // Then: las etiquetas HTML se eliminan y el producto se guarda con el texto limpio
        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('productos_mayoristas', [
            'distribuidor_id' => $distribuidor->id,
            'nombre' => 'Azúcar Rubia 50kg',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Saco',
            'descripcion' => 'Saco de azúcar pura.',
        ]);
    }

    /**
     * HU-02: Convencion decimal(10,2) - se rechaza un precio con mas de dos
     * decimales que la columna redondearia en silencio (desfase con el
     * precio unitario sugerido).
     */

    // php artisan test --filter=test_rechaza_publicacion_con_precio_de_mas_de_dos_decimales
    public function test_rechaza_publicacion_con_precio_de_mas_de_dos_decimales(): void
    {
        // HU-02 — Given: un distribuidor mayorista registrado y un payload con precio de tres decimales
        $distribuidor = User::create([
            'ruc_empresa' => '20601234567',
            'razon_social' => 'Distribuidora Central S.A.C.',
            'email_contacto' => 'distribuidor@central.pe',
            'telefono_whatsapp' => '+51987654321',
            'rol' => 'distribuidor',
            'password' => bcrypt('password123'),
        ]);

        $payload = [
            'nombre' => 'Detergente Rox 3 kg x 4',
            'categoria' => 'Limpieza',
            'presentacion' => 'Fardo',
            'unidades_por_bulto' => 4,
            'precio_bulto' => 56.005,
            'moq_cantidad_minima' => 10,
            'stock_disponible' => 40,
        ];

        // When: intenta publicar el producto con ese precio
        $response = $this->actingAs($distribuidor)->post(route('products.store'), $payload);

        // Then: se muestran errores de validación en el precio y no se persiste ningún producto
        $response->assertSessionHasErrors('precio_bulto');
        $this->assertDatabaseCount('productos_mayoristas', 0);
    }
}
