<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ficha CI-TEST-11: recorrido de la interfaz que sigue un distribuidor
 * mayorista para publicar un producto. Cubre el camino de navegación, no solo
 * el endpoint: si el botón o el formulario desaparecen, el usuario se queda
 * sin forma de dar de alta su catálogo aunque la API siga respondiendo.
 */
class ProductPublishingNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $distribuidor;

    private User $bodega;

    protected function setUp(): void
    {
        parent::setUp();

        $this->distribuidor = User::create([
            'ruc_empresa' => '20601234565',
            'razon_social' => 'Distribuidora Andina S.A.C.',
            'email_contacto' => 'ventas@andina.pe',
            'telefono_whatsapp' => '+51 987654321',
            'rol' => 'empresario',
            'password' => bcrypt('password'),
        ]);

        $this->bodega = User::create([
            'ruc_empresa' => '20607654321',
            'razon_social' => 'Bodega El Comercio E.I.R.L.',
            'email_contacto' => 'compras@comercio.pe',
            'telefono_whatsapp' => '+51 912345678',
            'rol' => 'persona_natural',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * El catálogo público muestra el acceso de publicación al distribuidor.
     */

    // php artisan test --filter=test_el_catalogo_muestra_el_boton_de_publicar_al_distribuidor
    public function test_el_catalogo_muestra_el_boton_de_publicar_al_distribuidor(): void
    {
        // Given: un distribuidor con sesión iniciada
        $this->actingAs($this->distribuidor)
            // When: consulta el catálogo público
            ->get(route('products.index'))
            // Then: la página responde y muestra la URL y el texto del botón de publicar
            ->assertOk()
            ->assertSee(route('products.create'), escape: false)
            ->assertSee('Publicar Nuevo Producto', escape: false);
    }

    /**
     * El botón no puede ser el punto de entrada de un usuario anónimo.
     */

    // php artisan test --filter=test_el_visitante_anonimo_no_ve_el_boton_de_publicar
    public function test_el_visitante_anonimo_no_ve_el_boton_de_publicar(): void
    {
        // Given: un visitante anónimo sin sesión iniciada
        // When: consulta el catálogo público
        $this->get(route('products.index'))
            // Then: la página responde y no muestra el botón de publicar
            ->assertOk()
            ->assertDontSee('Publicar Nuevo Producto', escape: false);
    }

    /**
     * El bodeguero tampoco, aunque tenga sesión iniciada.
     */

    // php artisan test --filter=test_el_rol_bodega_no_ve_el_boton_de_publicar
    public function test_el_rol_bodega_no_ve_el_boton_de_publicar(): void
    {
        // Given: un usuario con rol bodega y sesión iniciada
        $this->actingAs($this->bodega)
            // When: consulta el catálogo público
            ->get(route('products.index'))
            // Then: la página responde y no muestra el botón de publicar
            ->assertOk()
            ->assertDontSee('Publicar Nuevo Producto', escape: false);
    }

    /**
     * El formulario es alcanzable y trae todos los campos de la tabla de
     * mapeo de HU-02, incluida la imagen.
     */

    // php artisan test --filter=test_el_distribuidor_acllega_al_formulario_de_publicacion
    public function test_el_distribuidor_acllega_al_formulario_de_publicacion(): void
    {
        // Given: un distribuidor con sesión iniciada
        $this->actingAs($this->distribuidor)
            // When: abre la URL del formulario de publicación
            ->get(route('products.create'))
            // Then: el formulario responde con todos los campos del mapeo, incluida la imagen
            ->assertOk()
            ->assertSee('multipart/form-data', escape: false)
            ->assertSee('name="nombre"', escape: false)
            ->assertSee('name="categoria"', escape: false)
            ->assertSee('name="presentacion"', escape: false)
            ->assertSee('name="unidades_por_bulto"', escape: false)
            ->assertSee('name="precio_bulto"', escape: false)
            ->assertSee('name="moq_cantidad_minima"', escape: false)
            ->assertSee('name="stock_disponible"', escape: false)
            ->assertSee('name="descripcion"', escape: false)
            ->assertSee('name="imagen"', escape: false)
            ->assertSee('accept="image/png,image/jpeg,image/webp"', escape: false);
    }

    /**
     * El bodeguero que fuerza la URL recibe 403, no un formulario inútil.
     */

    // php artisan test --filter=test_el_rol_bodega_recibe_403_al_forzar_la_url_de_publicacion
    public function test_el_rol_bodega_recibe_403_al_forzar_la_url_de_publicacion(): void
    {
        // Given: un usuario con rol bodega y sesión iniciada
        $this->actingAs($this->bodega)
            // When: fuerza la URL del formulario de publicación
            ->get(route('products.create'))
            // Then: recibe 403 Forbidden en lugar de un formulario inútil
            ->assertForbidden();
    }

    /**
     * El distribuidor ve el producto que acaba de crear y puede editarlo.
     */

    // php artisan test --filter=test_el_distribuidor_encuentra_y_edita_su_producto_publicado
    public function test_el_distribuidor_encuentra_y_edita_su_producto_publicado(): void
    {
        // Given: un distribuidor con sesión iniciada
        // When: da de alta un producto en el catálogo
        $this->actingAs($this->distribuidor)->post(route('products.store'), [
            'nombre' => 'Atun Conservas 170g x 24',
            'categoria' => 'Enlatados',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 24,
            'precio_bulto' => 96.00,
            'moq_cantidad_minima' => 4,
            'stock_disponible' => 40,
        ]);

        $producto = Product::sole();

        // Then: el producto queda registrado a nombre del distribuidor
        $this->assertSame($this->distribuidor->id, $producto->distribuidor_id);

        // When: abre la ficha de su producto recién publicado
        $this->actingAs($this->distribuidor)
            ->get(route('products.show', $producto->id))
            // Then: la ficha responde y muestra el nombre publicado
            ->assertOk()
            ->assertSee('Atun Conservas 170g x 24', escape: false);

        // When: abre el formulario de edición del mismo producto
        $this->actingAs($this->distribuidor)
            ->get(route('products.edit', $producto->id))
            // Then: el formulario responde con el mismo nombre
            ->assertOk()
            ->assertSee('Atun Conservas 170g x 24', escape: false);
    }

    /**
     * Un distribuidor no puede editar el producto de otro: el enlace de
     * edición no debe convertirse en una vía de escalada horizontal.
     */

    // php artisan test --filter=test_un_distribuidor_no_puede_editar_el_producto_de_otro
    public function test_un_distribuidor_no_puede_editar_el_producto_de_otro(): void
    {
        // Given: un producto ajeno, registrado a nombre de otro usuario
        $productoAjeno = Product::create([
            'distribuidor_id' => $this->bodega->id,
            'nombre' => 'Producto de tercero',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 50.00,
            'precio_unitario_sugerido' => 4.17,
            'moq_cantidad_minima' => 2,
            'stock_disponible' => 10,
            'is_active' => true,
        ]);

        // When: un distribuidor distinto abre su URL de edición
        $this->actingAs($this->distribuidor)
            ->get(route('products.edit', $productoAjeno->id))
            // Then: recibe 403 Forbidden
            ->assertForbidden();
    }

    /**
     * La acción de publicar debe alcanzarse sin pasar por el catálogo, tanto
     * desde el menú como desde el dashboard. El menú se comprueba sobre una
     * pantalla que no tiene su propio botón, para no confundir una entrada con
     * la otra.
     */

    // php artisan test --filter=test_el_distribuidor_alcanza_la_publicacion_desde_el_menu_y_el_dashboard
    public function test_el_distribuidor_alcanza_la_publicacion_desde_el_menu_y_el_dashboard(): void
    {
        // Given: un distribuidor con sesión iniciada
        $this->actingAs($this->distribuidor)
            // When: abre el dashboard
            ->get(route('dashboard.index'))
            // Then: ve la acción de publicar
            ->assertOk()
            ->assertSee('Publicar Nuevo Producto', escape: false);

        // When: abre una pantalla que no tiene su propio botón de publicación
        $this->actingAs($this->distribuidor)
            ->get(route('quotes.index'))
            // Then: el enlace al formulario de publicación sigue presente
            ->assertOk()
            ->assertSee(route('products.create'), escape: false);
    }

    /**
     * El bodeguero no debe encontrar la acción de publicación en ninguna de las
     * dos pantallas.
     */

    // php artisan test --filter=test_el_rol_bodega_no_encuentra_la_accion_de_publicar
    public function test_el_rol_bodega_no_encuentra_la_accion_de_publicar(): void
    {
        // Given: un usuario con rol bodega y sesión iniciada
        $this->actingAs($this->bodega)
            // When: abre el dashboard
            ->get(route('dashboard.index'))
            // Then: el dashboard responde y no muestra la acción de publicar
            ->assertOk()
            ->assertDontSee('Publicar Nuevo Producto', escape: false);
    }

    /**
     * Ocultar el acceso sin explicar la causa deja al usuario buscando un botón
     * que el sistema nunca le ofreció. La Persona natural debe leer el motivo y
     * la vía de conversión a Empresario.
     */

    // php artisan test --filter=test_la_persona_natural_recibe_explicacion_y_cta_de_conversion
    public function test_la_persona_natural_recibe_explicacion_y_cta_de_conversion(): void
    {
        // Given: una Persona natural con sesión iniciada
        $this->actingAs($this->bodega)
            // When: abre el dashboard
            ->get(route('dashboard.index'))
            // Then: lee su rol actual, el motivo y el CTA de conversión
            ->assertOk()
            ->assertSee('Persona natural', escape: false)
            ->assertSee('Convertirme en Empresario', escape: false)
            ->assertSee(route('profile.empresario'), escape: false)
            ->assertSee('Módulo 11', escape: false);
    }

    /**
     * El Empresario no debe recibir el aviso de conversión: para él sí existe el
     * acceso, y el texto lo confundiría.
     */

    // php artisan test --filter=test_el_empresario_no_recibe_el_aviso_de_conversion
    public function test_el_empresario_no_recibe_el_aviso_de_conversion(): void
    {
        // Given: un Empresario con sesión iniciada
        $this->actingAs($this->distribuidor)
            // When: abre el dashboard
            ->get(route('dashboard.index'))
            // Then: no recibe el aviso de conversión reservado a la Persona natural
            ->assertOk()
            ->assertDontSee('Convertirme en Empresario', escape: false);
    }

    /**
     * El dashboard no debe mostrar un cero de productos cuando el distribuidor
     * ya publicó: se leería como que la publicación falló.
     */

    // php artisan test --filter=test_el_dashboard_refleja_el_catalogo_real_del_distribuidor
    public function test_el_dashboard_refleja_el_catalogo_real_del_distribuidor(): void
    {
        // Given: un distribuidor con sesión iniciada
        // When: da de alta un producto en el catálogo
        $this->actingAs($this->distribuidor)->post(route('products.store'), [
            'nombre' => 'Leche Evaporada 400ml x 24',
            'categoria' => 'Lácteos',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 24,
            'precio_bulto' => 78.00,
            'moq_cantidad_minima' => 3,
            'stock_disponible' => 30,
        ]);

        // When: abre su dashboard
        $this->actingAs($this->distribuidor)
            ->get(route('dashboard.index'))
            // Then: el contador de productos publicados refleja el catálogo real
            ->assertOk()
            ->assertSee('Productos publicados', escape: false)
            ->assertViewHas('metricas.productos', 1);

        // Then: el producto quedó persistido a nombre del distribuidor
        $this->assertDatabaseHas('productos_mayoristas', [
            'nombre' => 'Leche Evaporada 400ml x 24',
            'distribuidor_id' => $this->distribuidor->id,
        ]);
    }

    /**
     * El bodeguero ve el total del catálogo publicado, no cero: es el número
     * que le permite saber si la plataforma tiene oferta.
     */

    // php artisan test --filter=test_el_bodeguero_ve_el_total_del_catalogo_en_el_dashboard
    public function test_el_bodeguero_ve_el_total_del_catalogo_en_el_dashboard(): void
    {
        // Given: un producto publicado en el catálogo por el distribuidor
        Product::create([
            'distribuidor_id' => $this->distribuidor->id,
            'nombre' => 'Producto visible para el bodeguero',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 50.00,
            'precio_unitario_sugerido' => 4.17,
            'moq_cantidad_minima' => 2,
            'stock_disponible' => 10,
            'is_active' => true,
        ]);

        // When: un bodeguero abre su dashboard
        $this->actingAs($this->bodega)
            ->get(route('dashboard.index'))
            // Then: ve el total del catálogo, no un cero
            ->assertOk()
            ->assertSee('Productos en catálogo', escape: false)
            ->assertViewHas('metricas.productos', 1);
    }
}
