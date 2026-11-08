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
            'ruc_empresa' => '20601234565',
            'razon_social' => 'Distribuidora Central S.A.C.',
            'email_contacto' => 'ventas@central.pe',
            'telefono_whatsapp' => '+51 987 654 321',
            'rol' => 'empresario',
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

    // php artisan test --filter=test_visitante_anonimo_consulta_el_catalogo_sin_iniciar_sesion
    public function test_visitante_anonimo_consulta_el_catalogo_sin_iniciar_sesion(): void
    {
        // Given: un visitante anónimo y un producto publicado en el catálogo
        $this->producto();

        // When: consulta el catálogo sin iniciar sesión
        $response = $this->get(route('products.index'));

        // Then: el catálogo responde y muestra el producto con su MOQ
        $response->assertOk();
        $response->assertSee('Arroz Costeño 50kg');
        $response->assertSee('MOQ: 2 bultos');
    }

    /**
     * HU-03: El buscador filtra por nombre de producto.
     */

    // php artisan test --filter=test_el_buscador_filtra_por_nombre_de_producto
    public function test_el_buscador_filtra_por_nombre_de_producto(): void
    {
        // Given: dos productos con nombres distintos publicados en el catálogo
        $this->producto(['nombre' => 'Arroz Costeño 50kg']);
        $this->producto([
            'nombre' => 'Aceite Primor 1L x 12',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
        ]);

        // When: busca el término "Primor"
        $response = $this->get(route('products.index', ['q' => 'Primor']));

        // Then: solo aparece el producto cuyo nombre coincide
        $response->assertOk();
        $response->assertSee('Aceite Primor 1L x 12');
        $response->assertDontSee('Arroz Costeño 50kg');
    }

    /**
     * HU-03: El buscador también coincide contra la razón social del distribuidor.
     */

    // php artisan test --filter=test_el_buscador_encuentra_por_razon_social_del_distribuidor
    public function test_el_buscador_encuentra_por_razon_social_del_distribuidor(): void
    {
        // Given: un producto publicado por la distribuidora "Distribuidora Central"
        $this->producto();

        // When: busca por la razón social del distribuidor
        $response = $this->get(route('products.index', ['q' => 'Distribuidora Central']));

        // Then: aparece el producto de ese distribuidor
        $response->assertOk();
        $response->assertSee('Arroz Costeño 50kg');
    }

    /**
     * HU-03: El filtro por categoría acota el resultados del catálogo.
     */

    // php artisan test --filter=test_el_filtro_por_categoria_acota_los_resultados
    public function test_el_filtro_por_categoria_acota_los_resultados(): void
    {
        // Given: productos de dos categorías distintas en el catálogo
        $this->producto(['nombre' => 'Arroz Costeño 50kg', 'categoria' => 'Abarrotes']);
        $this->producto(['nombre' => 'Leche Gloria 1L', 'categoria' => 'Lácteos']);

        // When: filtra por la categoría "Lácteos"
        $response = $this->get(route('products.index', ['categoria' => 'Lácteos']));

        // Then: solo ve los productos de esa categoría
        $response->assertOk();
        $response->assertSee('Leche Gloria 1L');
        $response->assertDontSee('Arroz Costeño 50kg');
    }

    /**
     * HU-03: Paginación de 12 registros por página.
     */

    // php artisan test --filter=test_el_catalogo_pagina_de_doce_en_doce_registros
    public function test_el_catalogo_pagina_de_doce_en_doce_registros(): void
    {
        // Given: trece productos publicados en el catálogo
        Product::factory()->count(13)->create([
            'distribuidor_id' => $this->distribuidor->id,
        ]);

        // When: consulta la primera página del catálogo
        $primeraPagina = $this->get(route('products.index'))->viewData('products');

        // Then: la primera página ofrece 12 productos de un total de 13
        $this->assertSame(12, $primeraPagina->perPage());
        $this->assertSame(13, $primeraPagina->total());
        $this->assertCount(12, $primeraPagina->items());

        // When: pasa a la segunda página
        $segundaPagina = $this->get(route('products.index', ['page' => 2]))->viewData('products');

        // Then: la segunda página muestra el producto restante
        $this->assertCount(1, $segundaPagina->items());
    }

    /**
     * HU-03: Transparencia de impuestos, el catálogo desglosa el IGV del 18%.
     */

    // php artisan test --filter=test_el_catalogo_desglosa_el_igv_del_18_por_ciento
    public function test_el_catalogo_desglosa_el_igv_del_18_por_ciento(): void
    {
        // Given: un producto con precio de S/ 100.00 y una unidad por bulto
        $this->producto(['precio_bulto' => 100.00, 'unidades_por_bulto' => 1]);

        // When: consulta el catálogo
        $response = $this->get(route('products.index'));

        // Then: ve el desglose del IGV del 18% y el total con impuestos
        $response->assertOk();
        $response->assertSee('IGV 18%: S/ 18.00');
        $response->assertSee('total S/ 118.00');
    }

    /**
     * HU-03: Producto agotado, se muestra sin botón de compra y con la leyenda
     * "Temporalmente sin stock".
     */

    // php artisan test --filter=test_producto_sin_stock_se_marca_como_temporalmente_sin_stock
    public function test_producto_sin_stock_se_marca_como_temporalmente_sin_stock(): void
    {
        // Given: un producto agotado, con stock disponible en cero
        $this->producto(['nombre' => 'Leche evaporada 1L', 'stock_disponible' => 0]);

        // When: consulta el catálogo
        $response = $this->get(route('products.index'));

        // Then: el producto se marca como temporalmente sin stock y no ofrece el botón de cotizar
        $response->assertOk();
        $response->assertSee('Leche evaporada 1L');
        $response->assertSee('Temporalmente sin stock');
        $response->assertDontSee('Cotizar pedido');
    }

    /**
     * HU-03: Producto pausado por el distribuidor, tampoco es comprable.
     */

    // php artisan test --filter=test_producto_pausado_no_expone_el_boton_de_cotizar
    public function test_producto_pausado_no_expone_el_boton_de_cotizar(): void
    {
        // Given: un producto pausado por el distribuidor
        $this->producto(['is_active' => false]);

        // When: consulta el catálogo
        $response = $this->get(route('products.index'));

        // Then: el producto pausado se muestra con la leyenda "Temporalmente sin stock" sin botón de compra
        $response->assertOk();
        $response->assertSee('Temporalmente sin stock');
    }

    /**
     * HU-03: El detalle del producto es público y muestra el desglose de IGV.
     */

    // php artisan test --filter=test_el_detalle_del_producto_es_publico_y_muestra_el_igv
    public function test_el_detalle_del_producto_es_publico_y_muestra_el_igv(): void
    {
        // Given: un producto con precio de S/ 200.00 por bulto de 10 unidades y
        // 10 bultos en stock. El bulto es la unidad de comercio: el desglose no
        // puede calcularse sobre el precio unitario derivado (S/ 20.00).
        $producto = $this->producto([
            'nombre' => 'Detalle Público',
            'precio_bulto' => 200.00,
            'unidades_por_bulto' => 10,
            'stock_disponible' => 10,
        ]);

        // When: abre la página de detalle del producto sin iniciar sesión
        $response = $this->get(route('products.show', $producto->id));

        // Then: ve el producto con el IGV desglosado y el total con impuestos
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

    // php artisan test --filter=test_un_producto_pausado_sigue_siendo_accesible_en_su_url_directa
    public function test_un_producto_pausado_sigue_siendo_accesible_en_su_url_directa(): void
    {
        // Given: un producto pausado por el distribuidor
        $producto = $this->producto(['is_active' => false]);

        // When: accede a la URL directa del producto pausado
        // Then: responde 200 y muestra el aviso de pausado con el enlace al catálogo del distribuidor
        $this->get(route('products.show', $producto->id))
            ->assertOk()
            ->assertSee('Pausado por el distribuidor', escape: false)
            ->assertSee('Ver catálogo del distribuidor', escape: false);
    }

    /**
     * Un identificador que no existe sí debe seguir produciendo 404: la
     * diferencia entre producto pausado y producto inexistente importa.
     */

    // php artisan test --filter=test_un_identificador_inexistente_sigue_dando_404
    public function test_un_identificador_inexistente_sigue_dando_404(): void
    {
        // Given: ningún producto registrado con el identificador 999999
        // When: se solicita la URL directa de ese identificador
        // Then: la respuesta es 404 Not Found
        $this->get(route('products.show', 999999))->assertNotFound();
    }

    /**
     * RN-01: el visitante cotiza sin pasar por el login. El returnUrl sigue
     * existiendo para quien decide crear cuenta, pero ya no es el camino
     * obligatorio para generar la cotización.
     */

    // php artisan test --filter=test_el_visitante_cotiza_sin_pasar_por_el_login
    public function test_el_visitante_cotiza_sin_pasar_por_el_login(): void
    {
        // Given: un producto publicado en el catálogo
        $producto = $this->producto();

        // When: el visitante abre el detalle del producto
        // Then: el enlace de cotizar conduce al formulario público, no al login
        $this->get(route('products.show', $producto->id))
            ->assertOk()
            ->assertSee(route('quotes.create', ['producto' => $producto->id]), escape: false)
            ->assertDontSee(route('auth.login', ['returnUrl' => route('products.show', $producto->id)]), escape: false);
    }

    /**
     * HU-03: Ciclo completo, tras autenticarse el visitante vuelve al producto
     * que originó la redirección.
     */

    // php artisan test --filter=test_tras_iniciar_sesion_el_visitante_vuelve_al_producto_de_origen
    public function test_tras_iniciar_sesion_el_visitante_vuelve_al_producto_de_origen(): void
    {
        // Given: un producto publicado y una cuenta de bodega registrada
        $producto = $this->producto();

        $bodega = User::create([
            'ruc_empresa' => '10456789019',
            'razon_social' => 'Bodega Don Pepe',
            'email_contacto' => 'compras@donpepe.pe',
            'telefono_whatsapp' => '+51 999 888 777',
            'rol' => 'persona_natural',
            'password' => bcrypt('secreto123'),
        ]);

        // When: visita el login con la returnUrl y envía sus credenciales
        $this->get(route('auth.login', ['returnUrl' => route('products.show', $producto->id)]));

        $response = $this->post(route('auth.login.post'), [
            'email' => 'compras@donpepe.pe',
            'password' => 'secreto123',
        ]);

        // Then: queda autenticado y vuelve al producto de origen
        $response->assertRedirect('/products/'.$producto->id);
        $this->assertAuthenticatedAs($bodega);
    }

    /**
     * HU-03: Seguridad, un returnUrl externo se descarta (open redirect).
     */

    // php artisan test --filter=test_descarta_un_return_url_externo_para_prevenir_open_redirect
    public function test_descarta_un_return_url_externo_para_prevenir_open_redirect(): void
    {
        // Given: un returnUrl que apunta a un sitio externo malicioso
        // When: el visitante abre el login con ese returnUrl
        $response = $this->get(route('auth.login', ['returnUrl' => 'https://sitio-malicioso.pe/robo']));

        // Then: la URL externa se descarta, no queda registrada y no se muestra en la página
        $response->assertOk();
        $this->assertNull(session('url.intended'));
        $response->assertDontSee('https://sitio-malicioso.pe/robo');
    }

    /**
     * HU-03: La ruta /products/create sigue reservada a distribuidores, no la
     * captura el comodín público {product}.
     */

    // php artisan test --filter=test_la_ruta_de_creacion_no_es_capturada_por_el_comodin_publico
    public function test_la_ruta_de_creacion_no_es_capturada_por_el_comodin_publico(): void
    {
        // Given: un visitante anónimo sin credenciales
        // When: solicita la ruta de creación de productos
        // Then: es redirigido al login en lugar de la ruta pública
        $this->get(route('products.create'))->assertRedirect(route('login'));
    }

    /**
     * HU-03: La publicación de productos sigue protegida por RBAC.
     */

    // php artisan test --filter=test_la_publicacion_de_productos_sigue_siendo_exclusiva_del_distribuidor
    public function test_la_publicacion_de_productos_sigue_siendo_exclusiva_del_distribuidor(): void
    {
        // Given: un usuario autenticado con rol bodega
        $bodega = User::create([
            'ruc_empresa' => '10456789019',
            'razon_social' => 'Bodega Don Pepe',
            'email_contacto' => 'compras@donpepe.pe',
            'telefono_whatsapp' => '+51 999 888 777',
            'rol' => 'persona_natural',
            'password' => bcrypt('password123'),
        ]);

        // When: intenta acceder a la ruta de creación de productos
        // Then: recibe la respuesta 403 Forbidden
        $this->actingAs($bodega)->get(route('products.create'))->assertForbidden();
    }
}
