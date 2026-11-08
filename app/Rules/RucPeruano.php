<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * RN-02: el RUC peruano se valida con el algoritmo Modulo 11 y solo se aceptan
 * los prefijos 10 (persona natural con negocio) y 20 (persona juridica).
 */
class RucPeruano implements ValidationRule
{
    private const PESOS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    private const PREFIJOS = ['10', '20'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ruc = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (strlen($ruc) !== 11) {
            $fail('El RUC debe tener 11 dígitos.');

            return;
        }

        if (! in_array(substr($ruc, 0, 2), self::PREFIJOS, true)) {
            $fail('El RUC debe empezar con 10 (persona natural con negocio) o 20 (persona jurídica).');

            return;
        }

        if (! self::valido($ruc)) {
            $fail('El dígito verificador del RUC no es válido según el Módulo 11.');
        }
    }

    /**
     * Verifica el dígito verificador de un RUC de 11 dígitos.
     */
    public static function valido(string $ruc): bool
    {
        if (preg_match('/^\d{11}$/', $ruc) !== 1) {
            return false;
        }

        $suma = 0;

        for ($i = 0; $i < 10; $i++) {
            $suma += (int) $ruc[$i] * self::PESOS[$i];
        }

        $resto = 11 - ($suma % 11);

        if ($resto === 10) {
            $resto = 0;
        } elseif ($resto === 11) {
            $resto = 1;
        }

        return $resto === (int) $ruc[10];
    }
}
