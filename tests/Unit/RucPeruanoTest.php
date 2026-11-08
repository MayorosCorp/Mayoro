<?php

namespace Tests\Unit;

use App\Rules\RucPeruano;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * RN-02: el RUC peruano se valida con Modulo 11 y acepta los prefijos 10 y 20.
 */
class RucPeruanoTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function rucsValidos(): array
    {
        return [
            'persona juridica' => ['20123456786'],
            'otra persona juridica' => ['20987654326'],
            'persona natural con negocio' => ['10456789019'],
        ];
    }

    /**
     * @dataProvider rucsValidos
     */
    public function test_acepta_ruc_con_prefijo_y_digito_verificador_correctos(string $ruc): void
    {
        $validator = Validator::make(
            ['ruc_empresa' => $ruc],
            ['ruc_empresa' => [new RucPeruano]]
        );

        $this->assertTrue($validator->passes(), (string) $validator->errors()->first('ruc_empresa'));
    }

    public function test_rechaza_ruc_con_digito_verificador_incorrecto(): void
    {
        $validator = Validator::make(
            ['ruc_empresa' => '20123456788'],
            ['ruc_empresa' => [new RucPeruano]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('Módulo 11', (string) $validator->errors()->first('ruc_empresa'));
    }

    public function test_rechaza_ruc_con_prefijo_distinto_de_10_o_20(): void
    {
        $validator = Validator::make(
            ['ruc_empresa' => '15123456787'],
            ['ruc_empresa' => [new RucPeruano]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('10', (string) $validator->errors()->first('ruc_empresa'));
    }

    public function test_rechaza_ruc_que_no_tiene_11_digitos(): void
    {
        $validator = Validator::make(
            ['ruc_empresa' => '2012345678'],
            ['ruc_empresa' => [new RucPeruano]]
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('11 dígitos', (string) $validator->errors()->first('ruc_empresa'));
    }
}
