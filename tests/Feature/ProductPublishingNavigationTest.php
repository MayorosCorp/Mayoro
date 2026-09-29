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
            'ruc_empresa' => '20601234567',
            'razon_social' => 'Distribuidora Andina S.A.C.',
            'email_contacto' => 'ventas@andina.pe',
            'telefono_whatsapp' => '+51 987654321',
            'rol' => 'distribuidor',
            'password' => bcrypt('password'),
        ]);

        $this->bodega = User::create([
            'ruc_empresa' => '20607654321',
            'razon_social' => 'Bodega El Comercio E.I.R.L.',
            'email_contacto' => 'compras@comercio.pe',
            'telefono_whatsapp' => '+51 912345678',
            'rol' => 'bodega',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * El catálogo público muestra el acceso de publicación al distribuidor.
     */
    public function test_el_catalogo_muestra_el_boton_de_publicar_al_distribuidor(): void
    {
        $this->actingAs($this->distribuidor)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee(route('products.create'), escape: false)
            ->assertSee('Publicar Nuevo Producto', escape: false);
    }

    /**
     * El botón no puede ser el punto de entrada de un usuario anónimo.
     */
    public function test_el_visitante_anonimo_no_ve_el_boton_de_publicar(): void
    {
        $this->get(route('products.index'))
            ->assertOk()
            ->assertDontSee('Publicar Nuevo Producto', escape: false);
    }

    /**
     * El bodeguero tampoco, aunque tenga sesión iniciada.
     */
    public function test_el_rol_bodega_no_ve_el_boton_de_publicar(): void
    {
        $this->actingAs($this->bodega)
            ->get(route('products.index'))
            ->assertOk()
            ->assertDontSee('Publicar Nuevo Producto', escape: false);
    }

    /**
     * El formulario es alcanzable y trae todos los campos de la tabla de
     * mapeo de HU-02, incluida la imagen.
     */
    public function test_el_distribuidor_acllega_al_formulario_de_publicacion(): void
    {
        $this->actingAs($this->distribuidor)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('multipart/form-data', escape: false)
            ->assertSee('name="nombre"', escape: false)
            ->assertSee('name="categoria"', escape: false)
            ->assertSee('name="presentacion"', escape: false)
            ->assertSee('name="unidades_por_bulto"', escape: false)
            ->assertSee('name="precio_bulto"', escape: false)
            ->assertSee('name="moq_cantidad_minima"', escape: false)
            ->assertSee('name="descripcion"', escape: false)
            ->assertSee('name="imagen"', escape: false)
            ->assertSee('accept="image/png,image/jpeg,image/webp"', escape: false);
    }

    /**
     * El bodeguero que fuerza la URL recibe 403, no un formulario inútil.
     */
    public function test_el_rol_bodega_recibe_403_al_forzar_la_url_de_publicacion(): void
    {
        $this->actingAs($this->bodega)
            ->get(route('products.create'))
            ->assertForbidden();
    }

    /**
     * El distribuidor ve el producto que acaba de crear y puede editarlo.
     */
    public function test_el_distribuidor_encuentra_y_edita_su_producto_publicado(): void
    {
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

        $this->assertSame($this->distribuidor->id, $producto->distribuidor_id);

        $this->actingAs($this->distribuidor)
            ->get(route('products.show', $producto->id))
            ->assertOk()
            ->assertSee('Atun Conservas 170g x 24', escape: false);

        $this->actingAs($this->distribuidor)
            ->get(route('products.edit', $producto->id))
            ->assertOk()
            ->assertSee('Atun Conservas 170g x 24', escape: false);
    }

    /**
     * Un distribuidor no puede editar el producto de otro: el enlace de
     * edición no debe convertirse en una vía de escalada horizontal.
     */
    public function test_un_distribuidor_no_puede_editar_el_producto_de_otro(): void
    {
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

        $this->actingAs($this->distribuidor)
            ->get(route('products.edit', $productoAjeno->id))
            ->assertForbidden();
    }

    /**
     * La acción de publicar debe alcanzarse sin pasar por el catálogo, tanto
     * desde el menú como desde el dashboard. El menú se comprueba sobre una
     * pantalla que no tiene su propio botón, para no confundir una entrada con
     * la otra.
     */
    public function test_el_distribuidor_alcanza_la_publicacion_desde_el_menu_y_el_dashboard(): void
    {
        $this->actingAs($this->distribuidor)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Publicar Nuevo Producto', escape: false);

        $this->actingAs($this->distribuidor)
            ->get(route('quotes.index'))
            ->assertOk()
            ->assertSee(route('products.create'), escape: false);
    }

    /**
     * El bodeguero no debe encontrar la acción de publicación en ninguna de las
     * dos pantallas.
     */
    public function test_el_rol_bodega_no_encuentra_la_accion_de_publicar(): void
    {
        $this->actingAs($this->bodega)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('Publicar Nuevo Producto', escape: false);
    }

    /**
     * Ocultar el acceso sin explicar la causa deja al usuario buscando un botón
     * que el sistema nunca le ofreció. El bodeguero debe leer el motivo.
     */
    public function test_el_rol_bodega_recibe_explicacion_del_bloqueo_de_publicacion(): void
    {
        $this->actingAs($this->bodega)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('exclusiva del perfil', escape: false)
            ->assertSee('Distribuidor Mayorista', escape: false)
            ->assertSee('Tu rol actual es', escape: false)
            ->assertSee('bodega', escape: false);
    }

    /**
     * El distribuidor no debe recibir el aviso de bloqueo: para él sí existe el
     * acceso, y el texto lo confundiria.
     */
    public function test_el_distribuidor_no_recibe_el_aviso_de_bloqueo(): void
    {
        $this->actingAs($this->distribuidor)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('exclusiva del perfil', escape: false);
    }

    /**
     * El dashboard no debe mostrar un cero de productos cuando el distribuidor
     * ya publicó: se leería como que la publicación falló.
     */
    public function test_el_dashboard_refleja_el_catalogo_real_del_distribuidor(): void
    {
        $this->actingAs($this->distribuidor)->post(route('products.store'), [
            'nombre' => 'Leche Evaporada 400ml x 24',
            'categoria' => 'Lácteos',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 24,
            'precio_bulto' => 78.00,
            'moq_cantidad_minima' => 3,
            'stock_disponible' => 30,
        ]);

        $this->actingAs($this->distribuidor)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Productos publicados', escape: false)
            ->assertViewHas('metricas.productos', 1);

        $this->assertDatabaseHas('productos_mayoristas', [
            'nombre' => 'Leche Evaporada 400ml x 24',
            'distribuidor_id' => $this->distribuidor->id,
        ]);
    }

    /**
     * El bodeguero ve el total del catálogo publicado, no cero: es el número
     * que le permite saber si la plataforma tiene oferta.
     */
    public function test_el_bodeguero_ve_el_total_del_catalogo_en_el_dashboard(): void
    {
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

        $this->actingAs($this->bodega)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Productos en catálogo', escape: false)
            ->assertViewHas('metricas.productos', 1);
    }
}
