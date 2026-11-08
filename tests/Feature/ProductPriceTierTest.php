<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RN-04: precio base, MOQ y tramos de descuento por volumen mutuamente
 * excluyentes. El tramo aplicable es el de mayor umbral que no supera la
 * cantidad pedida; la cotizacion congela el precio y el umbral usados.
 */
class ProductPriceTierTest extends TestCase
{
    use RefreshDatabase;

    private User $distribuidor;

    private User $bodega;

    private Product $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->distribuidor = User::factory()->empresario()->create([
            'razon_social' => 'Distribuidora Volumen S.A.C.',
            'telefono_whatsapp' => '+51987654321',
        ]);

        $this->bodega = User::factory()->personaNatural()->create([
            'razon_social' => 'Bodega Volumen',
        ]);

        $this->producto = Product::factory()->create([
            'distribuidor_id' => $this->distribuidor->id,
            'nombre' => 'Aceite por Volumen 1L x 12',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 120.00,
            'precio_unitario_sugerido' => 10.00,
            'moq_cantidad_minima' => 5,
            'stock_disponible' => 500,
            'is_active' => true,
        ]);

        $this->producto->tramosPrecio()->createMany([
            ['cantidad_minima' => 10, 'precio_bulto' => 110.00],
            ['cantidad_minima' => 20, 'precio_bulto' => 100.00],
        ]);
    }

    private function payloadProducto(array $reemplazos = []): array
    {
        return array_merge([
            'nombre' => 'Arroz Volumen 50 kg',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Saco',
            'unidades_por_bulto' => 1,
            'precio_bulto' => 120.00,
            'moq_cantidad_minima' => 5,
            'stock_disponible' => 300,
            'descripcion' => 'Producto con tramos.',
        ], $reemplazos);
    }

    // php artisan test --filter=test_calcula_precio_en_el_moq_devuelve_precio_base
    public function test_calcula_precio_en_el_moq_devuelve_precio_base(): void
    {
        // Given: un producto con MOQ 5 y tramos desde 10 y 20 bultos
        // When: se cotiza el minimo permitido
        // Then: no hay tramo aplicable y rige el precio base
        $this->assertSame(120.00, $this->producto->precioBultoPara(5));
        $this->assertNull($this->producto->tramoPrecioPara(5));
    }

    // php artisan test --filter=test_calcula_precio_antes_del_primer_umbral_devuelve_precio_base
    public function test_calcula_precio_antes_del_primer_umbral_devuelve_precio_base(): void
    {
        // Given: el primer tramo empieza en 10 bultos
        // When: se cotiza 9 bultos, uno menos que el umbral
        // Then: se mantiene el precio base
        $this->assertSame(120.00, $this->producto->precioBultoPara(9));
    }

    // php artisan test --filter=test_calcula_precio_en_umbral_exacto_aplica_tramo
    public function test_calcula_precio_en_umbral_exacto_aplica_tramo(): void
    {
        // Given: un tramo cuyo umbral es 10 bultos
        // When: se cotizan exactamente 10 bultos
        // Then: se aplica ese tramo y no el precio base
        $this->assertSame(110.00, $this->producto->precioBultoPara(10));
        $this->assertSame(10, $this->producto->tramoPrecioPara(10)?->cantidad_minima);
    }

    // php artisan test --filter=test_calcula_precio_un_bulto_antes_del_umbral_conserva_tramo_anterior
    public function test_calcula_precio_un_bulto_antes_del_umbral_conserva_tramo_anterior(): void
    {
        // Given: tramos en 10 y 20 bultos
        // When: se cotizan 19 bultos, uno menos que el segundo umbral
        // Then: se aplica el primer tramo, nunca una mezcla de ambos
        $this->assertSame(110.00, $this->producto->precioBultoPara(19));
    }

    // php artisan test --filter=test_calcula_precio_sobre_ultimo_umbral_aplica_mejor_tramo
    public function test_calcula_precio_sobre_ultimo_umbral_aplica_mejor_tramo(): void
    {
        // Given: el tramo de mayor umbral es 20 bultos
        // When: se cotiza en el umbral y por encima de el
        // Then: se aplica el tramo de 20 bultos
        $this->assertSame(100.00, $this->producto->precioBultoPara(20));
        $this->assertSame(100.00, $this->producto->precioBultoPara(50));
    }

    // php artisan test --filter=test_calcula_precio_sin_tramos_devuelve_precio_base
    public function test_calcula_precio_sin_tramos_devuelve_precio_base(): void
    {
        // Given: un producto sin tramos configurados
        $producto = Product::factory()->create([
            'distribuidor_id' => $this->distribuidor->id,
            'precio_bulto' => 90.00,
            'moq_cantidad_minima' => 2,
            'stock_disponible' => 100,
        ]);

        // When: se cotiza cualquier cantidad
        // Then: el precio base no cambia
        $this->assertSame(90.00, $producto->precioBultoPara(2));
        $this->assertSame(90.00, $producto->precioBultoPara(100));
    }

    // php artisan test --filter=test_publica_producto_con_tramos_desordenados_los_guarda_ordenados
    public function test_publica_producto_con_tramos_desordenados_los_guarda_ordenados(): void
    {
        // Given: un Empresario que envia los tramos en orden inverso
        $response = $this->actingAs($this->distribuidor)->post(
            route('products.store'),
            $this->payloadProducto([
                'tramos' => [
                    ['cantidad_minima' => 20, 'precio_bulto' => 100.00],
                    ['cantidad_minima' => 10, 'precio_bulto' => 110.00],
                    ['cantidad_minima' => '', 'precio_bulto' => ''],
                ],
            ])
        );

        // When: el servidor publica el producto
        // Then: guarda los dos tramos validos en orden ascendente y descarta la fila vacia
        $response->assertSessionDoesntHaveErrors();
        $producto = Product::query()->where('nombre', 'Arroz Volumen 50 kg')->sole();

        $this->assertSame([10, 20], $producto->tramosPrecio->pluck('cantidad_minima')->all());
        $this->assertSame(['110.00', '100.00'], $producto->tramosPrecio->pluck('precio_bulto')->all());
    }

    // php artisan test --filter=test_publica_tramo_con_umbral_en_el_moq_lo_rechaza
    public function test_publica_tramo_con_umbral_en_el_moq_lo_rechaza(): void
    {
        // Given: un tramo que empieza en el propio MOQ
        $response = $this->actingAs($this->distribuidor)->post(
            route('products.store'),
            $this->payloadProducto([
                'tramos' => [['cantidad_minima' => 5, 'precio_bulto' => 110.00]],
            ])
        );

        // When: se valida
        // Then: se rechaza porque en el MOQ ya rige el precio base
        $response->assertSessionHasErrors('tramos.0.cantidad_minima');
        $this->assertDatabaseCount('producto_tramos_precio', 2);
    }

    // php artisan test --filter=test_publica_tramo_con_precio_igual_al_base_lo_rechaza
    public function test_publica_tramo_con_precio_igual_al_base_lo_rechaza(): void
    {
        // Given: un tramo que no descuenta nada
        $response = $this->actingAs($this->distribuidor)->post(
            route('products.store'),
            $this->payloadProducto([
                'tramos' => [['cantidad_minima' => 10, 'precio_bulto' => 120.00]],
            ])
        );

        // When: se valida
        // Then: se exige un precio menor al base
        $response->assertSessionHasErrors('tramos.0.precio_bulto');
    }

    // php artisan test --filter=test_publica_tramos_con_umbral_duplicado_los_rechaza
    public function test_publica_tramos_con_umbral_duplicado_los_rechaza(): void
    {
        // Given: dos tramos con el mismo umbral
        $response = $this->actingAs($this->distribuidor)->post(
            route('products.store'),
            $this->payloadProducto([
                'tramos' => [
                    ['cantidad_minima' => 10, 'precio_bulto' => 110.00],
                    ['cantidad_minima' => 10, 'precio_bulto' => 105.00],
                ],
            ])
        );

        // When: se valida
        // Then: la ambiguedad de precios queda prohibida
        $response->assertSessionHasErrors('tramos.1.cantidad_minima');
    }

    // php artisan test --filter=test_publica_tramo_sin_descuento_sobre_el_anterior_lo_rechaza
    public function test_publica_tramo_sin_descuento_sobre_el_anterior_lo_rechaza(): void
    {
        // Given: un segundo tramo mas caro que el primero
        $response = $this->actingAs($this->distribuidor)->post(
            route('products.store'),
            $this->payloadProducto([
                'tramos' => [
                    ['cantidad_minima' => 10, 'precio_bulto' => 110.00],
                    ['cantidad_minima' => 20, 'precio_bulto' => 115.00],
                ],
            ])
        );

        // When: se valida
        // Then: comprar mas nunca puede encarecer el bulto
        $response->assertSessionHasErrors('tramos.1.precio_bulto');
    }

    // php artisan test --filter=test_actualiza_producto_con_nuevos_tramos_reemplaza_los_anteriores
    public function test_actualiza_producto_con_nuevos_tramos_reemplaza_los_anteriores(): void
    {
        // Given: el producto del Empresario con dos tramos
        // When: guarda una edicion con un solo tramo distinto
        $response = $this->actingAs($this->distribuidor)->put(
            route('products.update', $this->producto),
            $this->payloadProducto([
                'nombre' => $this->producto->nombre,
                'unidades_por_bulto' => 12,
                'tramos' => [['cantidad_minima' => 15, 'precio_bulto' => 105.00]],
            ])
        );

        // Then: el conjunto anterior se reemplaza por completo
        $response->assertSessionDoesntHaveErrors();
        $tramos = $this->producto->fresh()->tramosPrecio;

        $this->assertCount(1, $tramos);
        $this->assertSame(15, $tramos->first()->cantidad_minima);
        $this->assertSame('105.00', $tramos->first()->precio_bulto);
    }

    // php artisan test --filter=test_actualiza_producto_sin_tramos_elimina_los_existentes
    public function test_actualiza_producto_sin_tramos_elimina_los_existentes(): void
    {
        // Given: el producto con tramos
        // When: el Empresario guarda la edicion sin ninguna fila de tramo
        $this->actingAs($this->distribuidor)->put(
            route('products.update', $this->producto),
            $this->payloadProducto([
                'nombre' => $this->producto->nombre,
                'unidades_por_bulto' => 12,
                'tramos' => [['cantidad_minima' => '', 'precio_bulto' => '']],
            ])
        )->assertSessionDoesntHaveErrors();

        // Then: el producto vuelve a cotizar solo con precio base
        $this->assertDatabaseCount('producto_tramos_precio', 0);
        $this->assertSame(120.00, $this->producto->fresh()->precioBultoPara(20));
    }

    // php artisan test --filter=test_cotiza_en_un_tramo_guarda_precio_y_snapshot
    public function test_cotiza_en_un_tramo_guarda_precio_y_snapshot(): void
    {
        // Given: una Persona natural que cotiza 10 bultos
        $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ])->assertSessionDoesntHaveErrors();

        // When: se registra la cotizacion
        $quote = Quote::sole();

        // Then: usa el tramo, calcula sus totales y congela el umbral
        $this->assertSame('110.00', $quote->precio_bulto);
        $this->assertSame(10, $quote->tramo_cantidad_minima);
        $this->assertSame('9.17', $quote->precio_unitario);
        $this->assertSame('1100.00', $quote->subtotal);
        $this->assertSame('198.00', $quote->igv);
        $this->assertSame('1298.00', $quote->total);

        // And: aunque el distribuidor cambie sus tramos, la cotizacion no se mueve
        ProductPriceTier::query()->where('producto_id', $this->producto->id)->delete();
        $this->producto->update(['precio_bulto' => 200.00]);

        $this->assertSame('110.00', $quote->fresh()->precio_bulto);
        $this->assertSame(10, $quote->fresh()->tramo_cantidad_minima);
    }

    // php artisan test --filter=test_cotiza_en_el_moq_conserva_precio_base
    public function test_cotiza_en_el_moq_conserva_precio_base(): void
    {
        // Given: una cotizacion por el minimo permitido
        $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 5,
        ])->assertSessionDoesntHaveErrors();

        // When: se persiste
        $quote = Quote::sole();

        // Then: no se inventa un descuento y no se marca tramo
        $this->assertSame('120.00', $quote->precio_bulto);
        $this->assertNull($quote->tramo_cantidad_minima);
        $this->assertSame('600.00', $quote->subtotal);
        $this->assertSame('708.00', $quote->total);
    }

    // php artisan test --filter=test_cotiza_como_visitante_con_tramo_lo_refleja_en_el_resumen
    public function test_cotiza_como_visitante_con_tramo_lo_refleja_en_el_resumen(): void
    {
        // Given: un visitante sin cuenta que cotiza 20 bultos
        $this->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 20,
        ])->assertRedirect(route('quotes.efimera'));

        // When: abre su resumen efimero
        $response = $this->get(route('quotes.efimera'));

        // Then: ve el precio del tramo y su explicacion, sin persistir la cotizacion
        $response->assertOk();
        $response->assertSee('S/ 100.00');
        $response->assertSee('S/ 2,000.00');
        $response->assertSee('desde 20 bultos');
        $this->assertSame(100.00, session('cotizacion_efimera')['precio_bulto']);
        $this->assertSame(20, session('cotizacion_efimera')['tramo_cantidad_minima']);
        $this->assertDatabaseCount('cotizaciones_b2b', 0);
    }

    // php artisan test --filter=test_muestra_ficha_con_tramos_publicados
    public function test_muestra_ficha_con_tramos_publicados(): void
    {
        // Given: un producto con tramos publicados
        // When: un visitante abre su ficha
        $response = $this->get(route('products.show', $this->producto));

        // Then: la tabla explica los umbrales y sus precios
        $response->assertOk();
        $response->assertSee('data-testid="producto-tramos"', escape: false);
        $response->assertSee('S/ 110.00');
        $response->assertSee('S/ 100.00');
        $response->assertSee('Desde 20');
    }

    // php artisan test --filter=test_genera_whatsapp_con_tramo_aplicado_lo_menciona
    public function test_genera_whatsapp_con_tramo_aplicado_lo_menciona(): void
    {
        // Given: una cotizacion que cae en el tramo de 10 bultos
        $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ])->assertSessionDoesntHaveErrors();

        // When: se decodifica el mensaje de WhatsApp
        $mensaje = rawurldecode((string) parse_url((string) Quote::sole()->url_whatsapp, PHP_URL_QUERY));

        // Then: el distribuidor recibe el precio y el tramo que se aplicaron
        $this->assertStringContainsString('Precio por volumen (desde 10 bultos): S/ 110.00', $mensaje);
        $this->assertStringContainsString('Total estimado: S/ 1,298.00', $mensaje);
    }
}
