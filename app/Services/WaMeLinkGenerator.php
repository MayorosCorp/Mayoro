<?php

namespace App\Services;

use App\Contracts\WhatsAppLinkGenerator;

class WaMeLinkGenerator implements WhatsAppLinkGenerator
{
    /**
     * Construye el enlace oficial https://wa.me/<E.164>?text=<mensaje codificado>.
     *
     * Codificación: los espacios se.normalizan a %20 y los saltos de línea a %0A,
     * tal como exige el criterio de aceptación HU-04.CA-02.
     */
    public function generate(string $phone, string $message): string
    {
        $telefono = $this->normalizePhone($phone);

        return sprintf('https://wa.me/%s?text=%s', $telefono, $this->encode($message));
    }

    /**
     * Normaliza un teléfono peruano al formato E.164 sin signos ni espacios.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '51')) {
            return $digits;
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 9) {
            return '51'.$digits;
        }

        return $digits;
    }

    /**
     * Determina si el teléfono es utilizable como destino de WhatsApp.
     */
    public function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^51\d{9}$/', $this->normalizePhone($phone));
    }

    /**
     * Codifica el mensaje para query string: %20 en espacios y %0A en saltos de línea.
     */
    private function encode(string $message): string
    {
        $normalizado = str_replace(["\r\n", "\r"], "\n", $message);

        return rawurlencode($normalizado);
    }
}
