<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Ficha CI-TEST-13: enumeración `estado_stock` de HU-03 y su exposición en el
 * catálogo y en la ficha del producto.
 *
 * El requisito declara tres situaciones y una sola leyenda visible:
 *   - Producto pausado por el distribuidor: se muestra opaco, sin botón de
 *     compra y con la leyenda "Temporalmente sin stock".
 *   - Producto agotado: mismo tratamiento.
 *   - Producto disponible: comprable.
 *
 * La enumeración existe para distinguir la causa, que la leyenda por sí sola
 * no expresa, y para poder citar el campo del requisito en la trazabilidad.
 */
class ProductStockStateTest extends TestCase
{
    use RefreshDatabase;

    private User $distribuidor;

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
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function producto(array $atributos = []): Product
    {
        return Product::create(array_merge([
            'distribuidor_id' => $this->distribuidor->id,
            'nombre' => 'Producto de prueba',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 60.00,
            'precio_unitario_sugerido' => 5.00,
            'moq_cantidad_minima' => 3,
            'stock_disponible' => 25,
            'is_active' => true,
        ], $atributos));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function estados(): array
    {
        return [
            'activo con existencias' => [['is_active' => true, 'stock_disponible' => 25], 'disponible', 'Disponible'],
            'activo sin existencias' => [['is_active' => true, 'stock_disponible' => 0], 'sin_stock', 'Temporalmente sin stock'],
            'pausado con existencias' => [['is_active' => false, 'stock_disponible' => 25], 'pausado', 'Pausado por el distribuidor'],
            'pausado y agotado' => [['is_active' => false, 'stock_disponible' => 0], 'pausado', 'Pausado por el distribuidor'],
        ];
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    #[DataProvider('estados')]

    // php artisan test --filter=test_la_enumeracion_estado_stock_clasifica_el_producto
    public function test_la_enumeracion_estado_stock_clasifica_el_producto(
        array $atributos,
        string $estadoEsperado,
        string $etiquetaEsperada
    ): void {
        $producto = $this->producto($atributos);

        $this->assertSame($estadoEsperado, $producto->estado_stock);
        $this->assertSame($etiquetaEsperada, $producto->etiqueta_estado_stock);
        $this->assertSame($estadoEsperado !== 'disponible', $producto->esta_agotado);
    }

    /**
     * El catálogo no debe ofrecer compra ni una etiqueta "Disponible" para lo
     * que no es adquirible.
     *
     * @param  array<string, mixed>  $atributos
     */
    #[DataProvider('estados')]

    // php artisan test --filter=test_el_catalogo_expone_la_leyenda_que_corresponde
    public function test_el_catalogo_expone_la_leyenda_que_corresponde(
        array $atributos,
        string $estadoEsperado,
        string $etiquetaEsperada
    ): void {
        $this->producto($atributos);

        $respuesta = $this->get(route('products.index'))->assertOk();

        if ($estadoEsperado === 'disponible') {
            $respuesta->assertDontSee('Temporalmente sin stock', escape: false);

            return;
        }

        // La leyenda exigida por el requisito aparece siempre que no es
        // comprable, tanto si la causa es pausa como si es agotamiento.
        $respuesta->assertSee('Temporalmente sin stock', escape: false);
        $respuesta->assertSee($etiquetaEsperada === 'Pausado por el distribuidor'
            ? 'Pausado por el distribuidor'
            : 'Temporalmente sin stock', escape: false);
    }

    /**
     * El bodeguero no puede cotizar un producto pausado ni agotado.
     *
     * @param  array<string, mixed>  $atributos
     */
    #[DataProvider('estados')]

    // php artisan test --filter=test_el_bodeguero_solo_puede_cotizar_productos_adquiribles
    public function test_el_bodeguero_solo_puede_cotizar_productos_adquiribles(
        array $atributos,
        string $estadoEsperado
    ): void {
        $producto = $this->producto($atributos);

        $bodega = User::create([
            'ruc_empresa' => '20607654321',
            'razon_social' => 'Bodega El Comercio E.I.R.L.',
            'email_contacto' => 'compras@comercio.pe',
            'telefono_whatsapp' => '+51 912345678',
            'rol' => 'bodega',
            'password' => bcrypt('password'),
        ]);

        $respuesta = $this->actingAs($bodega)
            ->get(route('products.show', $producto->id))
            ->assertOk();

        if ($estadoEsperado === 'disponible') {
            $respuesta->assertSee(route('quotes.create', ['producto' => $producto->id]), escape: false);
        } else {
            $respuesta->assertDontSee(route('quotes.create', ['producto' => $producto->id]), escape: false);
        }
    }

    /**
     * El motivo se distingue en la ficha: un producto pausado no promises fecha
     * de reposición, porque el distribuidor no la respondió.
     */

    // php artisan test --filter=test_la_ficha_distingue_pausado_de_agotado
    public function test_la_ficha_distingue_pausado_de_agotado(): void
    {
        $pausado = $this->producto(['nombre' => 'Producto pausado', 'is_active' => false, 'stock_disponible' => 40]);
        $agotado = $this->producto(['nombre' => 'Producto agotado', 'is_active' => true, 'stock_disponible' => 0]);

        $this->get(route('products.show', $pausado->id))
            ->assertOk()
            ->assertSee('detuvo la publicación de este producto', escape: false)
            ->assertDontSee('agotó sus existencias por el momento', escape: false);

        $this->get(route('products.show', $agotado->id))
            ->assertOk()
            ->assertSee('agotó sus existencias por el momento', escape: false)
            ->assertDontSee('detuvo la publicación de este producto', escape: false);
    }

    /**
     * Al dar de baja un producto, el enlace del catálogo sigue siendo válido:
     * es la regresión que motivó este trabajo.
     */

    // php artisan test --filter=test_el_enlace_del_catalogo_no_parte_al_dar_de_baja
    public function test_el_enlace_del_catalogo_no_parte_al_dar_de_baja(): void
    {
        $producto = $this->producto();

        $enlace = route('products.show', $producto->id);
        $this->get(route('products.index'))->assertOk()->assertSee($enlace, escape: false);

        $this->actingAs($this->distribuidor)
            ->delete(route('products.destroy', $producto->id))
            ->assertRedirect(route('products.index'));

        $this->assertFalse($producto->refresh()->is_active);

        // Antes de este arreglo, esta misma línea devolvía 404.
        $this->get($enlace)->assertOk();
    }

    /**
     * El distribuidor conserva el control sobre su producto pausado: puede
     * editarlo y republicarlo, porque la baja es una decisión suya.
     */

    // php artisan test --filter=test_el_distribuidor_puede_republicar_su_producto_pausado
    public function test_el_distribuidor_puede_republicar_su_producto_pausado(): void
    {
        $producto = $this->producto(['is_active' => false, 'stock_disponible' => 12]);

        $this->actingAs($this->distribuidor)
            ->get(route('products.edit', $producto->id))
            ->assertOk()
            ->assertSee('Republicar en el catálogo', escape: false)
            ->assertSee(route('products.reactivate', $producto->id), escape: false);

        $this->actingAs($this->distribuidor)
            ->patch(route('products.reactivate', $producto->id))
            ->assertRedirect(route('products.show', $producto->id));

        $producto->refresh();

        $this->assertTrue($producto->is_active);
        $this->assertSame('disponible', $producto->estado_stock);
    }

    /**
     * Guardar la edición de un producto pausado no debe republicarlo: el
     * catálogo público volvería a ofrecerlo sin que nadie lo pidiera.
     */

    // php artisan test --filter=test_editar_un_producto_pausado_no_lo_republica
    public function test_editar_un_producto_pausado_no_lo_republica(): void
    {
        $producto = $this->producto(['is_active' => false, 'stock_disponible' => 12]);

        $this->actingAs($this->distribuidor)
            ->put(route('products.update', $producto->id), [
                'nombre' => 'Producto de prueba editado',
                'categoria' => 'Abarrotes',
                'presentacion' => 'Caja',
                'unidades_por_bulto' => 12,
                'precio_bulto' => 72.00,
                'moq_cantidad_minima' => 3,
                'stock_disponible' => 40,
            ])
            ->assertRedirect(route('products.show', $producto->id));

        $producto->refresh();

        $this->assertFalse($producto->is_active, 'la edición no debe alterar el estado de publicación');
        $this->assertSame('Producto de prueba editado', $producto->nombre);
        $this->assertSame(40, $producto->stock_disponible);
    }

    /**
     * Republicar no puede convertirse en una vía para tomar el catálogo ajeno.
     */

    // php artisan test --filter=test_nadie_puede_republicar_el_producto_de_otro_distribuidor
    public function test_nadie_puede_republicar_el_producto_de_otro_distribuidor(): void
    {
        $producto = $this->producto(['is_active' => false]);

        $otro = User::create([
            'ruc_empresa' => '20609999999',
            'razon_social' => 'Otra Distribuidora S.A.C.',
            'email_contacto' => 'ventas@otra.pe',
            'telefono_whatsapp' => '+51 911111111',
            'rol' => 'distribuidor',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($otro)
            ->patch(route('products.reactivate', $producto->id))
            ->assertForbidden();

        $this->assertFalse($producto->refresh()->is_active);
    }

    /**
     * Republicar un producto ya activo es idempotente y no rompe nada.
     */

    // php artisan test --filter=test_republicar_un_producto_ya_activo_es_idempotente
    public function test_republicar_un_producto_ya_activo_es_idempotente(): void
    {
        $producto = $this->producto();

        $this->actingAs($this->distribuidor)
            ->patch(route('products.reactivate', $producto->id))
            ->assertRedirect(route('products.show', $producto->id))
            ->assertSessionHas('success');

        $this->assertTrue($producto->refresh()->is_active);
    }

    /**
     * Ficha CI-TEST-15: el stock declarado en el alta es el stock del producto.
     *
     * Defecto de origen: `ProductController@store` no guardaba `stock_disponible`,
     * la columna tiene `default(0)` y el formulario de alta no pedía el dato.
     * En consecuencia, todo producto publicado por la interfaz nacía agotado y
     * el catálogo lo mostraba como "Temporalmente sin stock", obligando al
     * distribuidor a una segunda pasada por la edición para vender algo. La
     * enumeración de estados de esta misma clase pasaba los ocho casos porque
     * construía el producto por el modelo, sin atravesar el alta real.
     */

    // php artisan test --filter=test_el_alta_persiste_el_stock_que_ingreso_el_distribuidor
    public function test_el_alta_persiste_el_stock_que_ingreso_el_distribuidor(): void
    {
        $this->actingAs($this->distribuidor)
            ->post(route('products.store'), $this->payloadAlta(['stock_disponible' => 120]))
            ->assertRedirect(route('products.index'))
            ->assertSessionHasNoErrors();

        $producto = Product::sole();

        $this->assertSame(120, $producto->stock_disponible);
        $this->assertSame('disponible', $producto->estado_stock);
        $this->assertFalse($producto->esta_agotado);
    }

    /**
     * El dato es obligatorio: sin él no se crea nada. Antes, omitirlo producía
     * un producto publicado con stock cero, que es un estado legítimo pero que
     * el distribuidor no había pedido en ningún momento.
     */

    // php artisan test --filter=test_publicar_sin_indicar_el_stock_no_crea_el_producto
    public function test_publicar_sin_indicar_el_stock_no_crea_el_producto(): void
    {
        $payload = $this->payloadAlta();
        unset($payload['stock_disponible']);

        $response = $this->actingAs($this->distribuidor)
            ->post(route('products.store'), $payload);

        $response->assertSessionHasErrors(['stock_disponible']);
        $this->assertSame(
            'Debe indicar cuántas unidades tiene disponibles',
            session('errors')->first('stock_disponible')
        );
        $this->assertDatabaseCount('productos_mayoristas', 0);
    }

    /**
     * Un stock negativo es un error de captura, no un estado del catálogo.
     */

    // php artisan test --filter=test_rechaza_un_stock_negativo
    public function test_rechaza_un_stock_negativo(): void
    {
        $this->actingAs($this->distribuidor)
            ->post(route('products.store'), $this->payloadAlta(['stock_disponible' => -5]))
            ->assertSessionHasErrors(['stock_disponible']);

        $this->assertDatabaseCount('productos_mayoristas', 0);
    }

    /**
     * El cero sigue siendo válido, porque el requisito contempla el producto
     * agotado. La diferencia es que ahora es una decisión informada: el campo
     * es obligatorio y su ayuda explica la consecuencia en el catálogo.
     */

    // php artisan test --filter=test_publicar_con_stock_cero_sigue_siendo_valido_y_deliberado
    public function test_publicar_con_stock_cero_sigue_siendo_valido_y_deliberado(): void
    {
        $this->actingAs($this->distribuidor)
            ->post(route('products.store'), $this->payloadAlta(['stock_disponible' => 0]))
            ->assertRedirect(route('products.index'))
            ->assertSessionHasNoErrors();

        $producto = Product::sole();

        $this->assertSame(0, $producto->stock_disponible);
        $this->assertSame('sin_stock', $producto->estado_stock);
    }

    /**
     * El formulario de alta debe ofrecer el campo y advertir de la consecuencia.
     */

    // php artisan test --filter=test_el_formulario_de_alta_pide_el_stock_y_explica_el_cero
    public function test_el_formulario_de_alta_pide_el_stock_y_explica_el_cero(): void
    {
        $this->actingAs($this->distribuidor)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('name="stock_disponible"', escape: false)
            ->assertSee('Temporalmente sin stock', escape: false);
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    private function payloadAlta(array $sobrescribir = []): array
    {
        return array_merge([
            'nombre' => 'Aceite Primor 1L x 12',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 120.00,
            'moq_cantidad_minima' => 5,
            'stock_disponible' => 240,
        ], $sobrescribir);
    }
}
