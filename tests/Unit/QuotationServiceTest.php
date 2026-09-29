<?php

namespace Tests\Unit;

use App\Contracts\WhatsAppLinkGenerator;
use App\Services\QuotationService;
use Mockery;
use PHPUnit\Framework\TestCase;

class QuotationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_no_permite_cotizar_si_la_cantidad_es_menor_al_moq(): void
    {
        // CA-01 — Given: un producto con MOQ de 10 unidades
        $whatsapp = Mockery::mock(WhatsAppLinkGenerator::class);
        $service = new QuotationService($whatsapp);

        // When: el cliente solicita 5 unidades
        $result = $service->canQuote(5, 10);

        // Then: no debe permitir generar la cotización
        $this->assertFalse($result);
    }

    public function test_permite_cotizar_si_la_cantidad_es_igual_al_moq(): void
    {
        // CA-01 — Given: un producto con MOQ de 10 unidades
        $whatsapp = Mockery::mock(WhatsAppLinkGenerator::class);
        $service = new QuotationService($whatsapp);

        // When: el cliente solicita exactamente 10 unidades
        $result = $service->canQuote(10, 10);

        // Then: debe permitir generar la cotización
        $this->assertTrue($result);
    }

    public function test_permite_cotizar_si_la_cantidad_es_mayor_al_moq(): void
    {
        // CA-01 — Given: un producto con MOQ de 10 unidades
        $whatsapp = Mockery::mock(WhatsAppLinkGenerator::class);
        $service = new QuotationService($whatsapp);

        // When: el cliente solicita 15 unidades
        $result = $service->canQuote(15, 10);

        // Then: debe permitir generar la cotización
        $this->assertTrue($result);
    }
}
