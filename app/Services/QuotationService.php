<?php

namespace App\Services;

use App\Contracts\WhatsAppLinkGenerator;
use App\Models\Product;
use App\Models\User;

class QuotationService
{
    /**
     * Tasa de IGV vigente en Peru aplicada a los precios referenciales del catalogo.
     */
    public const IGV_RATE = 0.18;

    public function __construct(
        private WhatsAppLinkGenerator $whatsapp
    ) {}

    public function canQuote(int $quantity, int $moq): bool
    {
        return $quantity >= $moq;
    }

    public function generateWhatsAppLink(string $phone, string $message): string
    {
        return $this->whatsapp->generate($phone, $message);
    }

    /**
     * Calcula el desglose economico de la cotizacion sobre el precio del bulto,
     * que es la unidad de comercio de Mayoro: subtotal, IGV 18% y total.
     *
     * @return array{subtotal: float, igv: float, total: float}
     */
    public function calcularTotales(float $precioBulto, int $cantidadBultos): array
    {
        $subtotal = round($precioBulto * $cantidadBultos, 2);
        $igv = round($subtotal * self::IGV_RATE, 2);

        return [
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => round($subtotal + $igv, 2),
        ];
    }

    /**
     * Compone el resumen comercial que el solicitante envia por WhatsApp. Si no
     * hay cuenta (visitante), el mensaje no inventa una identidad.
     */
    public function resumirCotizacion(?User $solicitante, Product $producto, int $cantidad): string
    {
        $precioBulto = (float) $producto->precio_bulto;
        $totales = $this->calcularTotales($precioBulto, $cantidad);
        $remitente = $solicitante?->razon_social ?: 'un comprador de Mayoro';

        return implode("\n", [
            'Hola '.($producto->distribuidor?->razon_social ?? '').', le escribo de parte de '.$remitente.'.',
            '',
            'Solicito cotizar el siguiente producto de su catalogo mayorista:',
            '',
            'Producto: '.$producto->nombre,
            'Presentacion: '.$producto->presentacion,
            'Unidades por bulto: '.$producto->unidades_por_bulto,
            'Cantidad de bultos: '.$cantidad.' (MOQ: '.$producto->moq_cantidad_minima.')',
            '',
            'Precio referencial: S/ '.number_format($precioBulto, 2).' por bulto',
            'Subtotal: S/ '.number_format($totales['subtotal'], 2),
            'IGV (18%): S/ '.number_format($totales['igv'], 2),
            'Total estimado: S/ '.number_format($totales['total'], 2),
            '',
            'Quedo atento a su confirmación. Gracias.',
        ]);
    }
}
