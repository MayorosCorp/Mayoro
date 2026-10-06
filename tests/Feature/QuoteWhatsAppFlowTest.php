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
            'ruc_empresa' => '20601234567',
            'razon_social' => 'Distribuidora Central S.A.C.',
            'email_contacto' => 'ventas@central.pe',
            'telefono_whatsapp' => '+51 987 654 321',
            'rol' => 'distribuidor',
            'password' => bcrypt('password123'),
        ]);

        $this->bodega = User::create([
            'ruc_empresa' => '10456789012',
            'razon_social' => 'Bodega Don Pepe',
            'email_contacto' => 'compras@donpepe.pe',
            'telefono_whatsapp' => '+51 999 888 777',
            'rol' => 'bodega',
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
            'is_active' => true,
        ]);
    }

    /**
     * HU-04: Camino Feliz - Registro de la cotización y generación del enlace
     * oficial de WhatsApp con el detalle del pedido.
     */

    // php artisan test tests/Feature/QuoteWhatsAppFlowTest.php --filter=test_bodeguero_registra_cotizacion_y_genera_enlace_de_whatsapp_codificado
    public function test_bodeguero_registra_cotizacion_y_genera_enlace_de_whatsapp_codificado(): void
    {
        $response = $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        $quote = Quote::sole();

        $response->assertRedirect(route('quotes.show', $quote));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cotizaciones_b2b', [
            'id' => $quote->id,
            'producto_id' => $this->producto->id,
            'bodega_id' => $this->bodega->id,
            'distribuidor_id' => $this->distribuidor->id,
            'cantidad_solicitada' => 10,
            'precio_unitario' => 10.00,
            'subtotal' => 100.00,
            'igv' => 18.00,
            'total' => 118.00,
            'telefono_destino' => '+51 987 654 321',
            'estado' => 'enviada',
        ]);

        $this->assertStringStartsWith('https://wa.me/51987654321?text=', (string) $quote->url_whatsapp);
        $this->assertStringContainsString('%20', (string) $quote->url_whatsapp);
        $this->assertStringContainsString('%0A', (string) $quote->url_whatsapp);
    }

    /**
     * HU-04: Regla de Negocio - Se bloquea la cotización cuando la cantidad
     * es inferior al MOQ fijado por el distribuidor.
     */

    // php artisan test tests/Feature/QuoteWhatsAppFlowTest.php --filter=test_bloquea_cotizacion_con_cantidad_inferior_al_moq
    public function test_bloquea_cotizacion_con_cantidad_inferior_al_moq(): void
    {
        $response = $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 2,
        ]);

        $response->assertSessionHasErrors('cantidad_solicitada');
        $this->assertDatabaseCount('cotizaciones_b2b', 0);

        $error = session('errors')->getBag('default')->first('cantidad_solicitada');
        $this->assertStringContainsString('La cantidad minima exigida por este distribuidor es de 5 bultos', $error);
    }

    /**
     * HU-04: Regla de Negocio - La cantidad igual al MOQ sí se acepta.
     */

    // php artisan test tests/Feature/QuoteWhatsAppFlowTest.php --filter=test_acepta_cotizacion_con_cantidad_igual_al_moq
    public function test_acepta_cotizacion_con_cantidad_igual_al_moq(): void
    {
        $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 5,
        ]);

        $this->assertDatabaseCount('cotizaciones_b2b', 1);
        $this->assertDatabaseHas('cotizaciones_b2b', [
            'cantidad_solicitada' => 5,
            'subtotal' => 50.00,
            'igv' => 9.00,
            'total' => 59.00,
        ]);
    }

    /**
     * HU-04: Excepción de Contingencia - Si el distribuidor no tiene un
     * teléfono utilizable, la cotización se registra igual y se ofrece el correo.
     */

    // php artisan test tests/Feature/QuoteWhatsAppFlowTest.php --filter=test_degrada_a_correo_cuando_el_distribuidor_no_tiene_telefono_valido
    public function test_degrada_a_correo_cuando_el_distribuidor_no_tiene_telefono_valido(): void
    {
        $this->distribuidor->update(['telefono_whatsapp' => 'no-es-un-telefono']);

        $response = $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

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
            'estado' => 'pendiente',
        ]);
    }

    /**
     * HU-04: Seguridad RBAC - Un distribuidor no puede generar cotizaciones
     * porque el flujo de compra es exclusivo del rol bodega (HTTP 403).
     */

    // php artisan test tests/Feature/QuoteWhatsAppFlowTest.php --filter=test_bloquea_cotizacion_a_usuario_con_rol_distribuidor_con_http_403_rbac
    public function test_bloquea_cotizacion_a_usuario_con_rol_distribuidor_con_http_403_rbac(): void
    {
        $response = $this->actingAs($this->distribuidor)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('cotizaciones_b2b', 0);
    }

    /**
     * HU-04: Seguridad Anónima - El visitante no autenticado es redirigido al login.
     */

    // php artisan test tests/Feature/QuoteWhatsAppFlowTest.php --filter=test_bloquea_cotizacion_a_visitante_no_autenticado
    public function test_bloquea_cotizacion_a_visitante_no_autenticado(): void
    {
        $response = $this->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('cotizaciones_b2b', 0);
    }

    /**
     * HU-04: Confidencialidad - Un comercio ajeno a la cotización no puede verla.
     */

    // php artisan test tests/Feature/QuoteWhatsAppFlowTest.php --filter=test_bloquea_la_visualizacion_de_una_cotizacion_ajena_con_http_403
    public function test_bloquea_la_visualizacion_de_una_cotizacion_ajena_con_http_403(): void
    {
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
            'rol' => 'bodega',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($intruso)->get(route('quotes.show', $quote))->assertForbidden();

        $this->actingAs($this->distribuidor)->get(route('quotes.show', $quote))->assertOk();
        $this->actingAs($this->bodega)->get(route('quotes.show', $quote))->assertOk();
    }

    /**
     * HU-04: La vista de detalle expone el enlace codificado y el desglose de IGV.
     */

    // php artisan test tests/Feature/QuoteWhatsAppFlowTest.php --filter=test_la_vista_de_detalle_muestra_el_desglose_y_el_enlace_de_whatsapp
    public function test_la_vista_de_detalle_muestra_el_desglose_y_el_enlace_de_whatsapp(): void
    {
        $this->actingAs($this->bodega)->post(route('quotes.store'), [
            'producto_id' => $this->producto->id,
            'cantidad_solicitada' => 10,
        ]);

        $quote = Quote::sole();

        $response = $this->actingAs($this->bodega)->get(route('quotes.show', $quote));

        $response->assertOk();
        $response->assertSee('118.00');
        $response->assertSee('Abrir chat de WhatsApp');
        $response->assertSee('https://wa.me/51987654321?text=', escape: false);
    }
}
