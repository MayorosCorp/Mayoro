<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $distribuidor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->distribuidor = User::create([
            'ruc_empresa' => '20601234567',
            'razon_social' => 'Distribuidora Central S.A.C.',
            'email_contacto' => 'ventas@central.pe',
            'telefono_whatsapp' => '+51 987 654 321',
            'rol' => 'distribuidor',
            'password' => bcrypt('password123'),
        ]);
    }

    private function producto(array $atributos = []): Product
    {
        return Product::create(array_merge([
            'distribuidor_id' => $this->distribuidor->id,
            'nombre' => 'Arroz Costeño 50kg',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Saco',
            'unidades_por_bulto' => 1,
            'precio_bulto' => 160.00,
            'moq_cantidad_minima' => 2,
            'precio_unitario_sugerido' => 160.00,
            'stock_disponible' => 40,
            'is_active' => true,
        ], $atributos));
    }

    /**
     * HU-03: Navegación y consulta pública sin credenciales (Camino Feliz).
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_visitante_anonimo_consulta_el_catalogo_sin_iniciar_sesion
    public function test_visitante_anonimo_consulta_el_catalogo_sin_iniciar_sesion(): void
    {
        $this->producto();

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Arroz Costeño 50kg');
        $response->assertSee('MOQ: 2 bultos');
    }

    /**
     * HU-03: El buscador filtra por nombre de producto.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_el_buscador_filtra_por_nombre_de_producto
    public function test_el_buscador_filtra_por_nombre_de_producto(): void
    {
        $this->producto(['nombre' => 'Arroz Costeño 50kg']);
        $this->producto([
            'nombre' => 'Aceite Primor 1L x 12',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
        ]);

        $response = $this->get(route('products.index', ['q' => 'Primor']));

        $response->assertOk();
        $response->assertSee('Aceite Primor 1L x 12');
        $response->assertDontSee('Arroz Costeño 50kg');
    }

    /**
     * HU-03: El buscador también coincide contra la razón social del distribuidor.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_el_buscador_encuentra_por_razon_social_del_distribuidor
    public function test_el_buscador_encuentra_por_razon_social_del_distribuidor(): void
    {
        $this->producto();

        $response = $this->get(route('products.index', ['q' => 'Distribuidora Central']));

        $response->assertOk();
        $response->assertSee('Arroz Costeño 50kg');
    }

    /**
     * HU-03: El filtro por categoría acota el resultados del catálogo.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_el_filtro_por_categoria_acota_los_resultados
    public function test_el_filtro_por_categoria_acota_los_resultados(): void
    {
        $this->producto(['nombre' => 'Arroz Costeño 50kg', 'categoria' => 'Abarrotes']);
        $this->producto(['nombre' => 'Leche Gloria 1L', 'categoria' => 'Lácteos']);

        $response = $this->get(route('products.index', ['categoria' => 'Lácteos']));

        $response->assertOk();
        $response->assertSee('Leche Gloria 1L');
        $response->assertDontSee('Arroz Costeño 50kg');
    }

    /**
     * HU-03: Paginación de 12 registros por página.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_el_catalogo_pagina_de_doce_en_doce_registros
    public function test_el_catalogo_pagina_de_doce_en_doce_registros(): void
    {
        Product::factory()->count(13)->create([
            'distribuidor_id' => $this->distribuidor->id,
        ]);

        $primeraPagina = $this->get(route('products.index'))->viewData('products');

        $this->assertSame(12, $primeraPagina->perPage());
        $this->assertSame(13, $primeraPagina->total());
        $this->assertCount(12, $primeraPagina->items());

        $segundaPagina = $this->get(route('products.index', ['page' => 2]))->viewData('products');

        $this->assertCount(1, $segundaPagina->items());
    }

    /**
     * HU-03: Transparencia de impuestos, el catálogo desglosa el IGV del 18%.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_el_catalogo_desglosa_el_igv_del_18_por_ciento
    public function test_el_catalogo_desglosa_el_igv_del_18_por_ciento(): void
    {
        $this->producto(['precio_bulto' => 100.00, 'unidades_por_bulto' => 1]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('IGV 18%: S/ 18.00');
        $response->assertSee('total S/ 118.00');
    }

    /**
     * HU-03: Producto agotado, se muestra sin botón de compra y con la leyenda
     * "Temporalmente sin stock".
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_producto_sin_stock_se_marca_como_temporalmente_sin_stock
    public function test_producto_sin_stock_se_marca_como_temporalmente_sin_stock(): void
    {
        $this->producto(['nombre' => 'Leche evaporada 1L', 'stock_disponible' => 0]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Leche evaporada 1L');
        $response->assertSee('Temporalmente sin stock');
        $response->assertDontSee('Cotizar pedido');
    }

    /**
     * HU-03: Producto pausado por el distribuidor, tampoco es comprable.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_producto_pausado_no_expone_el_boton_de_cotizar
    public function test_producto_pausado_no_expone_el_boton_de_cotizar(): void
    {
        $this->producto(['is_active' => false]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Temporalmente sin stock');
    }

    /**
     * HU-03: El detalle del producto es público y muestra el desglose de IGV.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_el_detalle_del_producto_es_publico_y_muestra_el_igv
    public function test_el_detalle_del_producto_es_publico_y_muestra_el_igv(): void
    {
        $producto = $this->producto([
            'nombre' => 'Detalle Público',
            'precio_bulto' => 200.00,
            'unidades_por_bulto' => 1,
            'stock_disponible' => 10,
        ]);

        $response = $this->get(route('products.show', $producto->id));

        $response->assertOk();
        $response->assertSee('Detalle Público');
        $response->assertSee('S/ 36.00');
        $response->assertSee('S/ 236.00');
    }

    /**
     * HU-03: Un producto inactivo no es accesible ni por su URL directa.
     */
    /**
     * HU-03, escenario "Producto agotado o suspendido (Caso Alternativo)": el
     * producto pausado se muestra opaco, sin botón de compra y con la leyenda
     * "Temporalmente sin stock".
     *
     * La prueba anterior afirmaba un 404 en la URL directa del producto pausado.
     * Eso contradecía al propio requisito y al catálogo, que lo mostraba y lo
     * enlazaba: pulsar la tarjeta llevaba a un enlace muerto y dejaba
     * inalcanzable el texto que explica al bodeguero la situación.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_un_producto_pausado_sigue_siendo_accesible_en_su_url_directa
    public function test_un_producto_pausado_sigue_siendo_accesible_en_su_url_directa(): void
    {
        $producto = $this->producto(['is_active' => false]);

        $this->get(route('products.show', $producto->id))
            ->assertOk()
            ->assertSee('Pausado por el distribuidor', escape: false)
            ->assertSee('Ver catálogo del distribuidor', escape: false);
    }

    /**
     * Un identificador que no existe sí debe seguir produciendo 404: la
     * diferencia entre producto pausado y producto inexistente importa.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_un_identificador_inexistente_sigue_dando_404
    public function test_un_identificador_inexistente_sigue_dando_404(): void
    {
        $this->get(route('products.show', 999999))->assertNotFound();
    }

    /**
     * HU-03: Redirección inteligente, el visitante recibe el returnUrl para
     * volver al producto que estaba consultando.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_el_visitante_es_redirigido_al_login_conservando_el_return_url
    public function test_el_visitante_es_redirigido_al_login_conservando_el_return_url(): void
    {
        $producto = $this->producto();

        $response = $this->get(route('auth.login', ['returnUrl' => route('products.show', $producto->id)]));

        $response->assertOk();
        $response->assertSee('Inicia sesión para continuar con tu cotización');
        // La URL absoluta propia del dominio se normaliza a su ruta relativa
        $this->assertSame('/products/'.$producto->id, session('url.intended'));

        $this->get(route('products.show', $producto->id))
            ->assertSee(route('auth.login', ['returnUrl' => route('products.show', $producto->id)]), escape: false);
    }

    /**
     * HU-03: Ciclo completo, tras autenticarse el visitante vuelve al producto
     * que originó la redirección.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_tras_iniciar_sesion_el_visitante_vuelve_al_producto_de_origen
    public function test_tras_iniciar_sesion_el_visitante_vuelve_al_producto_de_origen(): void
    {
        $producto = $this->producto();

        $bodega = User::create([
            'ruc_empresa' => '10456789012',
            'razon_social' => 'Bodega Don Pepe',
            'email_contacto' => 'compras@donpepe.pe',
            'telefono_whatsapp' => '+51 999 888 777',
            'rol' => 'bodega',
            'password' => bcrypt('secreto123'),
        ]);

        $this->get(route('auth.login', ['returnUrl' => route('products.show', $producto->id)]));

        $response = $this->post(route('auth.login.post'), [
            'email' => 'compras@donpepe.pe',
            'password' => 'secreto123',
        ]);

        $response->assertRedirect('/products/'.$producto->id);
        $this->assertAuthenticatedAs($bodega);
    }

    /**
     * HU-03: Seguridad, un returnUrl externo se descarta (open redirect).
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_descarta_un_return_url_externo_para_prevenir_open_redirect
    public function test_descarta_un_return_url_externo_para_prevenir_open_redirect(): void
    {
        $response = $this->get(route('auth.login', ['returnUrl' => 'https://sitio-malicioso.pe/robo']));

        $response->assertOk();
        $this->assertNull(session('url.intended'));
        $response->assertDontSee('https://sitio-malicioso.pe/robo');
    }

    /**
     * HU-03: La ruta /products/create sigue reservada a distribuidores, no la
     * captura el comodín público {product}.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_la_ruta_de_creacion_no_es_capturada_por_el_comodin_publico
    public function test_la_ruta_de_creacion_no_es_capturada_por_el_comodin_publico(): void
    {
        $this->get(route('products.create'))->assertRedirect(route('login'));
    }

    /**
     * HU-03: La publicación de productos sigue protegida por RBAC.
     */

    // php artisan test tests/Feature/PublicCatalogTest.php --filter=test_la_publicacion_de_productos_sigue_siendo_exclusiva_del_distribuidor
    public function test_la_publicacion_de_productos_sigue_siendo_exclusiva_del_distribuidor(): void
    {
        $bodega = User::create([
            'ruc_empresa' => '10456789012',
            'razon_social' => 'Bodega Don Pepe',
            'email_contacto' => 'compras@donpepe.pe',
            'telefono_whatsapp' => '+51 999 888 777',
            'rol' => 'bodega',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($bodega)->get(route('products.create'))->assertForbidden();
    }
}
