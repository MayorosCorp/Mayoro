<?php

namespace Tests\Unit;

use App\Contracts\WhatsAppLinkGenerator;
use App\Services\QuotationService;
use Mockery;
use PHPUnit\Framework\TestCase;

class QuotationWhatsAppTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    // php artisan test tests/Unit/QuotationWhatsAppTest.php --filter=test_genera_enlace_de_whatsapp_con_el_resumen_de_la_cotizacion

    public function test_genera_enlace_de_whatsapp_con_el_resumen_de_la_cotizacion(): void
    {
        // CA-03 — Given: un número de WhatsApp y un resumen de cotización válidos
        $whatsapp = Mockery::mock(WhatsAppLinkGenerator::class);

        $whatsapp->shouldReceive('generate')
            ->once()
            ->with(
                '51987654321',
                'Cotización: 10 unidades de Producto A'
            )
            ->andReturn(
                'https://wa.me/51987654321?text=Cotizaci%C3%B3n%3A%2010%20unidades%20de%20Producto%20A'
            );

        $service = new QuotationService($whatsapp);

        // When: se genera el enlace de WhatsApp
        $result = $service->generateWhatsAppLink(
            '51987654321',
            'Cotización: 10 unidades de Producto A'
        );

        // Then: debe devolver el enlace generado correctamente
        $this->assertSame(
            'https://wa.me/51987654321?text=Cotizaci%C3%B3n%3A%2010%20unidades%20de%20Producto%20A',
            $result
        );
    }
}
