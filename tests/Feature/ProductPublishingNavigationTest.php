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
}
