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
            'activo bajo el MOQ' => [['is_active' => true, 'stock_disponible' => 2], 'stock_insuficiente', 'Stock insuficiente para el pedido mínimo'],
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
        // Given: un producto con los atributos del caso (activo o pausado, con o sin existencias)
        $producto = $this->producto($atributos);

        // When: se consultan sus accesores de estado de stock
        // Then: devuelven el estado, la etiqueta y la marca de agotamiento esperados
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
        // Given: un producto con los atributos del caso (activo o pausado, con o sin existencias)
        $this->producto($atributos);

        // When: el catálogo público lo lista
        $respuesta = $this->get(route('products.index'))->assertOk();

        // Then: muestra la leyenda que corresponde a su estado
        if ($estadoEsperado === 'disponible') {
            $respuesta->assertDontSee('Temporalmente sin stock', escape: false);

            return;
        }

        // La leyenda exigida por el requisito aparece siempre que no es
        // comprable, tanto si la causa es pausa como si es agotamiento.
        $respuesta->assertSee('Temporalmente sin stock', escape: false);
        $respuesta->assertSee(match ($etiquetaEsperada) {
            'Pausado por el distribuidor' => 'Pausado por el distribuidor',
            'Stock insuficiente para el pedido mínimo' => 'Stock insuficiente para el pedido mínimo',
            default => 'Temporalmente sin stock',
        }, escape: false);
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
        // Given: un producto con los atributos del caso y un usuario bodega recién creado
        $producto = $this->producto($atributos);

        $bodega = User::create([
            'ruc_empresa' => '20607654321',
            'razon_social' => 'Bodega El Comercio E.I.R.L.',
            'email_contacto' => 'compras@comercio.pe',
            'telefono_whatsapp' => '+51 912345678',
            'rol' => 'bodega',
            'password' => bcrypt('password'),
        ]);

        // When: el bodeguero abre la ficha del producto
        $respuesta = $this->actingAs($bodega)
            ->get(route('products.show', $producto->id))
            ->assertOk();

        // Then: ve el enlace de cotización solo si el producto es adquirible
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
        // Given: un producto pausado y otro agotado en el catálogo
        $pausado = $this->producto(['nombre' => 'Producto pausado', 'is_active' => false, 'stock_disponible' => 40]);
        $agotado = $this->producto(['nombre' => 'Producto agotado', 'is_active' => true, 'stock_disponible' => 0]);

        // When: se consulta la ficha del producto pausado
        $this->get(route('products.show', $pausado->id))
            // Then: explica la pausa y no la causa de agotamiento
            ->assertOk()
            ->assertSee('detuvo la publicación de este producto', escape: false)
            ->assertDontSee('agotó sus existencias por el momento', escape: false);

        // When: se consulta la ficha del producto agotado
        $this->get(route('products.show', $agotado->id))
            // Then: explica el agotamiento y no la pausa
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
        // Given: un producto publicado en el catálogo
        $producto = $this->producto();

        $enlace = route('products.show', $producto->id);
        // When: el visitante abre el catálogo
        // Then: el enlace de la ficha aparece listado
        $this->get(route('products.index'))->assertOk()->assertSee($enlace, escape: false);

        // When: el distribuidor da de baja el producto
        $this->actingAs($this->distribuidor)
            ->delete(route('products.destroy', $producto->id))
            ->assertRedirect(route('products.index'));

        // Then: la baja deja el producto inactivo
        $this->assertFalse($producto->refresh()->is_active);

        // Then: el enlace del catálogo sigue respondiendo con 200
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
        // Given: un producto pausado por el distribuidor con existencias
        $producto = $this->producto(['is_active' => false, 'stock_disponible' => 12]);

        // When: el distribuidor abre su formulario de edición
        $this->actingAs($this->distribuidor)
            ->get(route('products.edit', $producto->id))
            // Then: el formulario ofrece republicar en el catálogo
            ->assertOk()
            ->assertSee('Republicar en el catálogo', escape: false)
            ->assertSee(route('products.reactivate', $producto->id), escape: false);

        // When: pulsa la acción de republicar
        $this->actingAs($this->distribuidor)
            ->patch(route('products.reactivate', $producto->id))
            // Then: la republicación redirige a la ficha del producto
            ->assertRedirect(route('products.show', $producto->id));

        $producto->refresh();

        // Then: el producto vuelve a estar activo y disponible
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
        // Given: un producto pausado por el distribuidor
        $producto = $this->producto(['is_active' => false, 'stock_disponible' => 12]);

        // When: el distribuidor guarda los cambios de su edición
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
            // Then: la edición se guarda y redirige a la ficha
            ->assertRedirect(route('products.show', $producto->id));

        $producto->refresh();

        // Then: el producto sigue pausado y conserva los datos editados
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
        // Given: un producto pausado de un distribuidor y otro distribuidor distinto
        $producto = $this->producto(['is_active' => false]);

        $otro = User::create([
            'ruc_empresa' => '20609999999',
            'razon_social' => 'Otra Distribuidora S.A.C.',
            'email_contacto' => 'ventas@otra.pe',
            'telefono_whatsapp' => '+51 911111111',
            'rol' => 'distribuidor',
            'password' => bcrypt('password'),
        ]);

        // When: el segundo distribuidor intenta republicar el producto ajeno
        $this->actingAs($otro)
            ->patch(route('products.reactivate', $producto->id))
            // Then: recibe 403 Forbidden
            ->assertForbidden();

        // Then: el producto ajeno sigue pausado
        $this->assertFalse($producto->refresh()->is_active);
    }

    /**
     * Republicar un producto ya activo es idempotente y no rompe nada.
     */

    // php artisan test --filter=test_republicar_un_producto_ya_activo_es_idempotente
    public function test_republicar_un_producto_ya_activo_es_idempotente(): void
    {
        // Given: un producto ya activo en el catálogo
        $producto = $this->producto();

        // When: su distribuidor vuelve a pulsar republicar
        $this->actingAs($this->distribuidor)
            ->patch(route('products.reactivate', $producto->id))
            // Then: la operación redirige a la ficha con mensaje de éxito
            ->assertRedirect(route('products.show', $producto->id))
            ->assertSessionHas('success');

        // Then: el producto sigue activo
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
        // Given: un distribuidor que completa el formulario de alta con 120 unidades de stock
        // When: publica el producto
        $this->actingAs($this->distribuidor)
            ->post(route('products.store'), $this->payloadAlta(['stock_disponible' => 120]))
            // Then: la publicación redirige al catálogo sin errores de validación
            ->assertRedirect(route('products.index'))
            ->assertSessionHasNoErrors();

        $producto = Product::sole();

        // Then: el stock ingresado persistió y el producto nace disponible
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
        // Given: el payload de alta sin el campo de stock
        $payload = $this->payloadAlta();
        unset($payload['stock_disponible']);

        // When: el distribuidor intenta publicar el producto
        $response = $this->actingAs($this->distribuidor)
            ->post(route('products.store'), $payload);

        // Then: la validación exige el stock, explica el motivo y no crea el producto
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
        // Given: el payload de alta con un stock negativo
        // When: el distribuidor intenta publicar el producto
        $this->actingAs($this->distribuidor)
            ->post(route('products.store'), $this->payloadAlta(['stock_disponible' => -5]))
            // Then: la validación rechaza el stock y no se crea el producto
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
        // Given: un distribuidor que completa el formulario de alta con stock en cero
        // When: publica el producto
        $this->actingAs($this->distribuidor)
            ->post(route('products.store'), $this->payloadAlta(['stock_disponible' => 0]))
            // Then: la publicación se acepta y redirige al catálogo sin errores
            ->assertRedirect(route('products.index'))
            ->assertSessionHasNoErrors();

        $producto = Product::sole();

        // Then: el producto queda con stock cero y estado sin_stock
        $this->assertSame(0, $producto->stock_disponible);
        $this->assertSame('sin_stock', $producto->estado_stock);
    }

    /**
     * El formulario de alta debe ofrecer el campo y advertir de la consecuencia.
     */

    // php artisan test --filter=test_el_formulario_de_alta_pide_el_stock_y_explica_el_cero
    public function test_el_formulario_de_alta_pide_el_stock_y_explica_el_cero(): void
    {
        // Given: un distribuidor con sesión iniciada
        $this->actingAs($this->distribuidor)
            // When: abre el formulario de alta de productos
            ->get(route('products.create'))
            // Then: el formulario incluye el campo de stock y explica la leyenda del cero
            ->assertOk()
            ->assertSee('name="stock_disponible"', escape: false)
            ->assertSee('Temporalmente sin stock', escape: false);
    }

    /**
     * RN-05: un stock intermedio entre 1 y MOQ-1 no es un estado vendible.
     * Se rechaza en el alta con una explicación, en lugar de publicar un
     * producto que el catálogo mostrará como no adquirible.
     */

    // php artisan test --filter=test_rechaza_un_stock_que_no_alcanza_el_moq
    public function test_rechaza_un_stock_que_no_alcanza_el_moq(): void
    {
        // Given: el payload de alta con MOQ 5 y solo 2 bultos en stock
        // When: el distribuidor intenta publicar el producto
        $response = $this->actingAs($this->distribuidor)
            ->post(route('products.store'), $this->payloadAlta([
                'moq_cantidad_minima' => 5,
                'stock_disponible' => 2,
            ]));

        // Then: la validación lo rechaza con el motivo y no crea el producto
        $response->assertSessionHasErrors(['stock_disponible']);
        $this->assertStringContainsString(
            'El stock debe ser 0 o al menos el pedido minimo de 5 bultos',
            (string) session('errors')->first('stock_disponible')
        );
        $this->assertDatabaseCount('productos_mayoristas', 0);
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
