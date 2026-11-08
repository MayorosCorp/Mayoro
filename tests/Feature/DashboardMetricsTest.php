<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ficha CI-TEST-16: las métricas del dashboard se aíslan por rol y por parte.
 * Antes, la bodega veía el total global de cotizaciones "pendientes" y el
 * distribuidor podía leer como propias las de otro distribuidor. El contador
 * tampoco coincidía con el único estado que Mayoro puede afirmar: "generada".
 */
class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private User $distribuidorA;

    private User $distribuidorB;

    private User $bodegaA;

    private User $bodegaB;

    private Product $productoA;

    private Product $productoB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->distribuidorA = $this->comercio('20601234565', 'Distribuidora Andina S.A.C.', 'ventas@andina.pe', 'empresario');
        $this->distribuidorB = $this->comercio('20609999994', 'Distribuidora del Sur S.A.C.', 'ventas@sur.pe', 'empresario');
        $this->bodegaA = $this->comercio('10456789019', 'Bodega Don Pepe', 'compras@donpepe.pe', 'persona_natural');
        $this->bodegaB = $this->comercio('10456789027', 'Bodega La Esquina', 'compras@esquina.pe', 'persona_natural');

        $this->productoA = $this->producto($this->distribuidorA, 'Producto A');
        $this->productoB = $this->producto($this->distribuidorB, 'Producto B');
    }

    /**
     * El distribuidor solo cuenta las cotizaciones que recibió.
     */

    // php artisan test --filter=test_el_distribuidor_solo_cuenta_sus_cotizaciones_recibidas
    public function test_el_distribuidor_solo_cuenta_sus_cotizaciones_recibidas(): void
    {
        // Given: una cotización para cada distribuidor
        $this->cotizacion($this->bodegaA, $this->distribuidorA, $this->productoA);
        $this->cotizacion($this->bodegaB, $this->distribuidorB, $this->productoB);

        // When: el distribuidor A abre su dashboard
        // Then: solo cuenta la suya y la etiqueta lo dice
        $this->actingAs($this->distribuidorA)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Cotizaciones recibidas', escape: false)
            ->assertViewHas('metricas.cotizaciones', 1);
    }

    /**
     * La bodega solo cuenta las cotizaciones que generó, no el total global.
     */

    // php artisan test --filter=test_la_bodega_solo_cuenta_sus_cotizaciones_generadas
    public function test_la_bodega_solo_cuenta_sus_cotizaciones_generadas(): void
    {
        // Given: una cotización generada por cada bodega
        $this->cotizacion($this->bodegaA, $this->distribuidorA, $this->productoA);
        $this->cotizacion($this->bodegaB, $this->distribuidorB, $this->productoB);

        // When: la bodega A abre su dashboard
        // Then: solo cuenta la propia y no la de la otra bodega
        $this->actingAs($this->bodegaA)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Cotizaciones generadas', escape: false)
            ->assertViewHas('metricas.cotizaciones', 1);
    }

    /**
     * El contador no incluye las cotizaciones que no llegaron a generarse.
     */

    // php artisan test --filter=test_el_contador_no_suma_cotizaciones_no_generadas
    public function test_el_contador_no_suma_cotizaciones_no_generadas(): void
    {
        // Given: una cotización generada y otra sin enlace por contingencia
        $this->cotizacion($this->bodegaA, $this->distribuidorA, $this->productoA, 'generada');

        Quote::create([
            'producto_id' => $this->productoA->id,
            'bodega_id' => $this->bodegaA->id,
            'distribuidor_id' => $this->distribuidorA->id,
            'cantidad_solicitada' => 5,
            'precio_bulto' => 120.00,
            'unidades_por_bulto' => 12,
            'precio_unitario' => 10.00,
            'subtotal' => 600.00,
            'igv' => 108.00,
            'total' => 708.00,
            'telefono_destino' => '+51 999 888 777',
            'url_whatsapp' => null,
            'estado' => 'rechazada',
        ]);

        // When: la bodega abre su dashboard
        // Then: solo cuenta la cotización en estado generada
        $this->actingAs($this->bodegaA)
            ->get(route('dashboard.index'))
            ->assertViewHas('metricas.cotizaciones', 1);
    }

    /**
     * El contador de productos no cotizables distingue la causa (pausado, sin
     * stock o bajo el MOQ) y no cruza la frontera entre distribuidores. La
     * bodega, en cambio, ve el catálogo publicado completo.
     */

    // php artisan test --filter=test_el_contador_de_no_cotizables_respeta_rol_y_propiedad
    public function test_el_contador_de_no_cotizables_respeta_rol_y_propiedad(): void
    {
        // Given: dos productos no cotizables del distribuidor A y uno del B
        $this->producto($this->distribuidorA, 'Pausado A', ['is_active' => false]);
        $this->producto($this->distribuidorA, 'Bajo MOQ A', ['stock_disponible' => 2]);
        $this->producto($this->distribuidorB, 'Agotado B', ['stock_disponible' => 0]);

        // When: cada rol abre su dashboard
        // Then: A solo cuenta lo suyo; la bodega cuenta lo publicado no cotizable
        $this->actingAs($this->distribuidorA)
            ->get(route('dashboard.index'))
            ->assertViewHas('metricas.noCotizables', 2);

        $this->actingAs($this->bodegaA)
            ->get(route('dashboard.index'))
            ->assertViewHas('metricas.noCotizables', 2);
    }

    private function comercio(string $ruc, string $razonSocial, string $email, string $rol): User
    {
        return User::create([
            'ruc_empresa' => $ruc,
            'razon_social' => $razonSocial,
            'email_contacto' => $email,
            'telefono_whatsapp' => '+51 987 654 321',
            'rol' => $rol,
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     */
    private function producto(User $distribuidor, string $nombre, array $sobrescribir = []): Product
    {
        return Product::create(array_merge([
            'distribuidor_id' => $distribuidor->id,
            'nombre' => $nombre,
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 120.00,
            'precio_unitario_sugerido' => 10.00,
            'moq_cantidad_minima' => 5,
            'stock_disponible' => 40,
            'is_active' => true,
        ], $sobrescribir));
    }

    private function cotizacion(User $bodega, User $distribuidor, Product $producto, string $estado = 'generada'): Quote
    {
        return Quote::create([
            'producto_id' => $producto->id,
            'bodega_id' => $bodega->id,
            'distribuidor_id' => $distribuidor->id,
            'cantidad_solicitada' => 10,
            'precio_bulto' => 120.00,
            'unidades_por_bulto' => 12,
            'precio_unitario' => 10.00,
            'subtotal' => 1200.00,
            'igv' => 216.00,
            'total' => 1416.00,
            'telefono_destino' => '+51 987 654 321',
            'url_whatsapp' => 'https://wa.me/51987654321?text=Hola',
            'estado' => $estado,
        ]);
    }
}
