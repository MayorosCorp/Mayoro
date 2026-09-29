<?php

namespace Tests\Unit;

use App\Services\WaMeLinkGenerator;
use PHPUnit\Framework\TestCase;

class WaMeLinkGeneratorTest extends TestCase
{
    private WaMeLinkGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new WaMeLinkGenerator;
    }

    /**
     * HU-04.CA-02 — Given: un teléfono peruano con prefijo internacional
     * When: se genera el enlace con un mensaje de una sola línea
     * Then: el enlace apunta a https://wa.me/51... con el texto codificado en %20
     */
    public function test_genera_enlace_wa_me_con_espacios_codificados_en_por_veinte(): void
    {
        $link = $this->generator->generate('51987654321', 'Cotizacion de 10 bultos');

        $this->assertSame(
            'https://wa.me/51987654321?text=Cotizacion%20de%2010%20bultos',
            $link
        );
        $this->assertStringContainsString('%20', $link);
        $this->assertStringNotContainsString('+', $link);
    }

    /**
     * HU-04.CA-02 — Given: un resumen comercial multilínea
     * When: se genera el enlace
     * Then: cada salto de línea se codifica como %0A y no como %0D%0A
     */
    public function test_codifica_los_saltos_de_linea_como_por_veinte_a(): void
    {
        $link = $this->generator->generate('51987654321', "Linea uno\nLinea dos");

        $this->assertStringContainsString('%0A', $link);
        $this->assertStringNotContainsString('%0D', $link);
        $this->assertSame(
            'https://wa.me/51987654321?text=Linea%20uno%0ALinea%20dos',
            $link
        );
    }

    /**
     * Given: un número local de 9 dígitos sin prefijo
     * When: se genera el enlace
     * Then: se normaliza al formato E.164 peruano con prefijo 51
     */
    public function test_normaliza_telefono_local_de_nueve_digitos_con_prefijo_51(): void
    {
        $this->assertSame('51987654321', $this->generator->normalizePhone('987654321'));
        $this->assertSame('51987654321', $this->generator->normalizePhone('+51 987 654 321'));
        $this->assertSame('51987654321', $this->generator->normalizePhone('(51) 987-654-321'));
    }

    /**
     * Given: distintos formatos de teléfono
     * When: se consulta su validez
     * Then: solo los que resuelven a 51 seguido de 9 dígitos se consideran utilizables
     */
    public function test_valida_el_telefono_como_destino_de_whatsapp(): void
    {
        $this->assertTrue($this->generator->isValidPhone('+51 987 654 321'));
        $this->assertTrue($this->generator->isValidPhone('51987654321'));
        $this->assertFalse($this->generator->isValidPhone('12345'));
        $this->assertFalse($this->generator->isValidPhone(''));
        $this->assertFalse($this->generator->isValidPhone('corrupto-abc'));
    }

    /**
     * Given: un mensaje con acentos y simbolos reservados
     * When: se genera el enlace
     * Then: todo se codifica en UTF-8 y los separadores no rompen la query string
     */
    public function test_codifica_acentos_y_simbolos_reservados_en_utf8(): void
    {
        $link = $this->generator->generate('51987654321', 'Total: S/ 1,180.00 & IGV');

        $this->assertStringContainsString('S%2F%201%2C180.00', $link);
        $this->assertStringContainsString('%26', $link);
    }
}
