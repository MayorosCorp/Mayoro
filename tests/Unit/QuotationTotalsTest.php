<?php

namespace Tests\Unit;

use App\Contracts\WhatsAppLinkGenerator;
use App\Services\QuotationService;
use Mockery;
use PHPUnit\Framework\TestCase;

class QuotationTotalsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * HU-04 — Given: un precio unitario referencial de S/ 10.00
     * When: la bodega cotiza 10 bultos
     * Then: el subtotal es S/ 100.00, el IGV 18% es S/ 18.00 y el total S/ 118.00
     */

    // php artisan test tests/Unit/QuotationTotalsTest.php --filter=test_calcula_subtotal_igv_y_total_con_tasa_de_igv_del_18_por_ciento
    public function test_calcula_subtotal_igv_y_total_con_tasa_de_igv_del_18_por_ciento(): void
    {
        $service = new QuotationService(Mockery::mock(WhatsAppLinkGenerator::class));

        $totales = $service->calcularTotales(10.00, 10);

        $this->assertSame(100.00, $totales['subtotal']);
        $this->assertSame(18.00, $totales['igv']);
        $this->assertSame(118.00, $totales['total']);
    }

    /**
     * Given: un precio unitario con decimales
     * When: se desglosa la cotización
     * Then: los importes se redondean a dos decimales para evitar residuos de punto flotante
     */

    // php artisan test tests/Unit/QuotationTotalsTest.php --filter=test_redondea_los_importes_a_dos_decimales
    public function test_redondea_los_importes_a_dos_decimales(): void
    {
        $service = new QuotationService(Mockery::mock(WhatsAppLinkGenerator::class));

        $totales = $service->calcularTotales(10.333, 3);

        $this->assertSame(31.00, $totales['subtotal']);
        $this->assertSame(5.58, $totales['igv']);
        $this->assertSame(36.58, $totales['total']);
    }

    /**
     * Given: la tasa de IGV declarada por el servicio
     * When: se inspecciona la constante
     * Then: debe ser 0.18 conforme a la normativa peruana
     */

    // php artisan test tests/Unit/QuotationTotalsTest.php --filter=test_expone_la_tasa_de_igv_vigente
    public function test_expone_la_tasa_de_igv_vigente(): void
    {
        $this->assertSame(0.18, QuotationService::IGV_RATE);
    }
}
