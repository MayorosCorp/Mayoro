<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteWhatsAppFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $distribuidor;

    private User $bodega;

    private Product $producto;

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

        $this->bodega = User::create([
            'ruc_empresa' => '10456789019',
            'razon_social' => 'Bodega Don Pepe',
            'email_contacto' => 'compras@donpepe.pe',
            'telefono_whatsapp' => '+51 999 888 777',
            'rol' => 'persona_natural',
            'password' => bcrypt('password123'),
        ]);

        $this->producto = Product::create([
            'distribuidor_id' => $this->distribuidor->id,
            'nombre' => 'Aceite Primor 1L x 12',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 120.00,
            'moq_cantidad_minima' => 5,
            'precio_unitario_sugerido' => 10.00,
            'stock_disponible' => 40,
            'is_active' => true,
        ]);
    }

    /**
     * HU-04: Camino Feliz - Registro de la cotización y generación del enlace
     * oficial de WhatsApp con el detalle del pedido.
     */

    // php artisan test --filter=test_persona_natural_registra_cotizacion_y_genera_enlace_de_whatsapp_codificado
    public function test_persona_natural_registra_cotizacion_y_genera_enlace_de_whatsapp_codificado(): void
    {
        // Given: una Persona natural autenticada y un producto con MOQ de 5 bultos y precio unitario de 10.00
        // When: registra una cotización de 10 bultos desde el controlador de cotizaciones
        $response = $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        // Then: la cotización se persiste con el desglose de IGV y se redirige al detalle con el enlace de WhatsApp codificado
        $quote = Quote::sole();

        $response->assertRedirect(route('quotes.show', $quote));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cotizaciones_b2b', [
            'id' => $quote->id,
            'producto_id' => $this->producto->id,
            'bodega_id' => $this->bodega->id,
            'distribuidor_id' => $this->distribuidor->id,
            'cantidad_solicitada' => 10,
            'precio_bulto' => 120.00,
            'unidades_por_bulto' => 12,
            'precio_unitario' => 10.00,
            'subtotal' => 1200.00,
            'igv' => 216.00,
            'total' => 1416.00,
            'telefono_destino' => '+51 987 654 321',
            'estado' => 'generada',
        ]);

        $this->assertStringStartsWith('https://wa.me/51987654321?text=', (string) $quote->url_whatsapp);
        $this->assertStringContainsString('%20', (string) $quote->url_whatsapp);
        $this->assertStringContainsString('%0A', (string) $quote->url_whatsapp);
    }

    /**
     * HU-04: Regla de Negocio - Se bloquea la cotización cuando la cantidad
     * es inferior al MOQ fijado por el distribuidor.
     */

    // php artisan test --filter=test_bloquea_cotizacion_con_cantidad_inferior_al_moq
    public function test_bloquea_cotizacion_con_cantidad_inferior_al_moq(): void
    {
        // Given: un producto cuyo distribuidor exige un MOQ de 5 bultos
        // When: el bodeguero intenta cotizar solo 2 bultos
        $response = $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 2,
        ]);

        // Then: la solicitud se rechaza, no se persiste ninguna cotización y se explica el MOQ mínimo
        $response->assertSessionHasErrors('cantidad_solicitada');
        $this->assertDatabaseCount('cotizaciones_b2b', 0);

        $error = session('errors')->getBag('default')->first('cantidad_solicitada');
        $this->assertStringContainsString('La cantidad minima exigida por este distribuidor es de 5 bultos', $error);
    }

    /**
     * HU-04: Regla de Negocio - La cantidad igual al MOQ sí se acepta.
     */

    // php artisan test --filter=test_acepta_cotizacion_con_cantidad_igual_al_moq
    public function test_acepta_cotizacion_con_cantidad_igual_al_moq(): void
    {
        // Given: un producto cuyo distribuidor exige un MOQ de 5 bultos
        // When: el bodeguero cotiza exactamente 5 bultos
        $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 5,
        ]);

        // Then: la cotización se registra con subtotal, IGV y total calculados sobre 5 bultos
        $this->assertDatabaseCount('cotizaciones_b2b', 1);
        $this->assertDatabaseHas('cotizaciones_b2b', [
            'cantidad_solicitada' => 5,
            'precio_bulto' => 120.00,
            'subtotal' => 600.00,
            'igv' => 108.00,
            'total' => 708.00,
        ]);
    }

    /**
     * HU-04: Excepción de Contingencia - Si el distribuidor no tiene un
     * teléfono utilizable, la cotización se registra igual y se ofrece el correo.
     */

    // php artisan test --filter=test_degrada_a_correo_cuando_el_distribuidor_no_tiene_telefono_valido
    public function test_degrada_a_correo_cuando_el_distribuidor_no_tiene_telefono_valido(): void
    {
        // Given: un distribuidor cuyo teléfono de WhatsApp no es utilizable
        $this->distribuidor->update(['telefono_whatsapp' => 'no-es-un-telefono']);

        // When: el bodeguero registra una cotización de 10 bultos para ese distribuidor
        $response = $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        // Then: la cotización queda pendiente sin enlace de WhatsApp y se ofrece el correo de contacto
        $quote = Quote::sole();

        $response->assertRedirect(route('quotes.show', $quote));
        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'ventas@central.pe',
            (string) session('error')
        );

        $this->assertDatabaseHas('cotizaciones_b2b', [
            'id' => $quote->id,
            'url_whatsapp' => null,
            'estado' => 'generada',
        ]);
    }

    /**
     * RN-02: el Empresario administra su catalogo y no genera solicitudes con
     * su propio rol. El flujo de compra es de la Persona natural o el visitante.
     */

    // php artisan test --filter=test_bloquea_cotizacion_a_empresario_con_http_403_rbac
    public function test_bloquea_cotizacion_a_empresario_con_http_403_rbac(): void
    {
        // Given: un usuario autenticado con rol Empresario
        // When: intenta registrar una cotizacion como si fuera comprador
        $response = $this->actingAs($this->distribuidor)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        // Then: la autorización lo rechaza con HTTP 403 y no se crea ninguna cotización
        $response->assertForbidden();
        $this->assertDatabaseCount('cotizaciones_b2b', 0);
    }

    /**
     * RN-01: el visitante sin cuenta tambien cotiza. Su solicitud no se persiste
     * porque no hay identidad a la que atribuir un historial.
     */

    // php artisan test --filter=test_visitante_genera_cotizacion_efimera_sin_persistirla
    public function test_visitante_genera_cotizacion_efimera_sin_persistirla(): void
    {
        // Given: un visitante sin autenticar en la aplicacion
        // When: registra una cotizacion de 10 bultos
        $response = $this->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        // Then: se le muestra un resumen efimero y no se escribe en la base
        $response->assertRedirect(route('quotes.efimera'));
        $this->assertDatabaseCount('cotizaciones_b2b', 0);
        $this->assertIsArray(session('cotizacion_efimera'));
    }

    // php artisan test --filter=test_el_resumen_efimero_muestra_producto_desglose_y_enlace_de_whatsapp
    public function test_el_resumen_efimero_muestra_producto_desglose_y_enlace_de_whatsapp(): void
    {
        // Given: un visitante que acaba de generar su cotizacion
        $this->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        // When: abre el resumen efimero
        $response = $this->get(route('quotes.efimera'));

        // Then: ve el producto, el desglose, el enlace y el aviso de que no se guardo
        $response->assertOk();
        $response->assertSee('Aceite Primor 1L x 12');
        $response->assertSee('S/ 1,416.00');
        $response->assertSee('https://wa.me/51987654321?text=', escape: false);
        $response->assertSee('Enlace efímero');
        $response->assertSee('no se guardó en la base de datos');
    }

    // php artisan test --filter=test_el_resumen_efimero_sin_datos_redirige_al_formulario
    public function test_el_resumen_efimero_sin_datos_redirige_al_formulario(): void
    {
        // Given: un visitante que abre el resumen sin haber cotizado
        // When: solicita la vista efimera
        // Then: se le devuelve al formulario con un aviso
        $this->get(route('quotes.efimera'))
            ->assertRedirect(route('quotes.create'))
            ->assertSessionHas('error');
    }

    /**
     * HU-04: Confidencialidad - Un comercio ajeno a la cotización no puede verla.
     */

    // php artisan test --filter=test_bloquea_la_visualizacion_de_una_cotizacion_ajena_con_http_403
    public function test_bloquea_la_visualizacion_de_una_cotizacion_ajena_con_http_403(): void
    {
        // Given: una cotización registrada por un bodeguero y un comercio ajeno a ella
        $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        $this->assertDatabaseCount('cotizaciones_b2b', 1);

        $quote = Quote::sole();

        $intruso = User::create([
            'ruc_empresa' => '20998877665',
            'razon_social' => 'Bodega Intrusa E.I.R.L.',
            'email_contacto' => 'intrusa@ajena.pe',
            'telefono_whatsapp' => '+51 977 666 555',
            'rol' => 'persona_natural',
            'password' => bcrypt('password123'),
        ]);

        // When: el comercio ajeno intenta ver la ficha de esa cotización
        $this->actingAs($intruso)->get(route('quotes.show', $quote))->assertForbidden();

        // Then: solo el distribuidor y el bodeguero dueños de la cotización pueden verla
        $this->actingAs($this->distribuidor)->get(route('quotes.show', $quote))->assertOk();
        $this->actingAs($this->bodega)->get(route('quotes.show', $quote))->assertOk();
    }

    /**
     * HU-04: La vista de detalle expone el enlace codificado y el desglose de IGV.
     */

    // php artisan test --filter=test_la_vista_de_detalle_muestra_el_desglose_y_el_enlace_de_whatsapp
    public function test_la_vista_de_detalle_muestra_el_desglose_y_el_enlace_de_whatsapp(): void
    {
        // Given: una cotización registrada por el bodeguero
        $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        $quote = Quote::sole();

        // When: el bodeguero visita la vista de detalle de su cotización
        $response = $this->actingAs($this->bodega)->get(route('quotes.show', $quote));

        // Then: se muestra el total con IGV y el enlace codificado de WhatsApp
        $response->assertOk();
        $response->assertSee('1,416.00');
        $response->assertSee('Abrir chat de WhatsApp');
        $response->assertSee('https://wa.me/51987654321?text=', escape: false);
    }

    /**
     * HU-03 + HU-04: un producto agotado no se puede cotizar aunque el cliente
     * fuerce el formulario. La regla se comprueba en el servidor.
     */

    // php artisan test --filter=test_rechaza_cotizacion_de_producto_sin_stock
    public function test_rechaza_cotizacion_de_producto_sin_stock(): void
    {
        // Given: un producto publicado pero sin existencias
        $this->producto->update(['stock_disponible' => 0]);

        // When: el bodeguero fuerza una cotización de 10 bultos
        $response = $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        // Then: se rechaza con un mensaje de disponibilidad y no se persiste nada
        $response->assertSessionHasErrors('producto_id');
        $this->assertStringContainsString(
            'temporalmente sin stock',
            (string) session('errors')->first('producto_id')
        );
        $this->assertDatabaseCount('cotizaciones_b2b', 0);
    }

    /**
     * No se puede cotizar más de lo que el distribuidor tiene en stock.
     */

    // php artisan test --filter=test_rechaza_cantidad_superior_al_stock_disponible
    public function test_rechaza_cantidad_superior_al_stock_disponible(): void
    {
        // Given: un producto con 12 bultos disponibles
        $this->producto->update(['stock_disponible' => 12]);

        // When: el bodeguero intenta cotizar 20 bultos
        $response = $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 20,
        ]);

        // Then: se explica el máximo real y no se crea la cotización
        $response->assertSessionHasErrors('cantidad_solicitada');
        $this->assertStringContainsString(
            'Solo hay 12 bultos disponibles',
            (string) session('errors')->first('cantidad_solicitada')
        );
        $this->assertDatabaseCount('cotizaciones_b2b', 0);
    }

    /**
     * Un producto pausado no vuelve al circuito de cotización por la puerta
     * trasera del formulario.
     */

    // php artisan test --filter=test_rechaza_cotizacion_de_producto_pausado
    public function test_rechaza_cotizacion_de_producto_pausado(): void
    {
        // Given: un producto retirado del catálogo
        $this->producto->update(['is_active' => false]);

        // When: el bodeguero fuerza la cotización
        $response = $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        // Then: el servidor lo rechaza
        $response->assertSessionHasErrors('producto_id');
        $this->assertDatabaseCount('cotizaciones_b2b', 0);
    }

    /**
     * El selector del formulario solo lista productos cotizables: publicados y
     * con stock suficiente para su MOQ. Ofrecer el resto sería prometer una
     * cotización que el propio servidor rechaza.
     */

    // php artisan test --filter=test_el_formulario_solo_ofrece_productos_cotizables
    public function test_el_formulario_solo_ofrece_productos_cotizables(): void
    {
        // Given: un producto cotizable y dos que no lo son
        $cotizable = $this->producto;

        $agotado = Product::create([
            'distribuidor_id' => $this->distribuidor->id,
            'nombre' => 'Producto agotado',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 60.00,
            'moq_cantidad_minima' => 3,
            'precio_unitario_sugerido' => 5.00,
            'stock_disponible' => 0,
            'is_active' => true,
        ]);

        $insuficiente = Product::create([
            'distribuidor_id' => $this->distribuidor->id,
            'nombre' => 'Producto con stock insuficiente',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 60.00,
            'moq_cantidad_minima' => 5,
            'precio_unitario_sugerido' => 5.00,
            'stock_disponible' => 2,
            'is_active' => true,
        ]);

        // When: el bodeguero abre el formulario de cotización
        $response = $this->actingAs($this->bodega)->get(route('quotes.create'));

        // Then: solo aparece el producto cotizable
        $response->assertOk();
        $response->assertSee($cotizable->nombre);
        $response->assertDontSee($agotado->nombre);
        $response->assertDontSee($insuficiente->nombre);
    }
}
