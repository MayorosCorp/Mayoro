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
     * Calcula el desglose economico de la cotizacion: subtotal, IGV 18% y total.
     *
     * @return array{subtotal: float, igv: float, total: float}
     */
    public function calcularTotales(float $precioUnitario, int $cantidad): array
    {
        $subtotal = round($precioUnitario * $cantidad, 2);
        $igv = round($subtotal * self::IGV_RATE, 2);

        return [
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => round($subtotal + $igv, 2),
        ];
    }

    /**
     * Compone el resumen comercial que el bodeguero envia por WhatsApp.
     */
    public function resumirCotizacion(User $bodega, Product $producto, int $cantidad): string
    {
        $totales = $this->calcularTotales($producto->precio_unitario_calculado, $cantidad);
        $precioUnitario = $producto->precio_unitario_calculado;

        return implode("\n", [
            'Hola '.($producto->distribuidor?->razon_social ?? '').', le escribo de parte de '.($bodega->razon_social ?? '').'.',
            '',
            'Solicito cotizar el siguiente producto de su catalogo mayorista:',
            '',
            'Producto: '.$producto->nombre,
            'Presentacion: '.$producto->presentacion,
            'Unidades por bulto: '.$producto->unidades_por_bulto,
            'Cantidad de bultos: '.$cantidad.' (MOQ: '.$producto->moq_cantidad_minima.')',
            '',
            'Precio referencial: S/ '.number_format($precioUnitario, 2).' por unidad',
            'Subtotal: S/ '.number_format($totales['subtotal'], 2),
            'IGV (18%): S/ '.number_format($totales['igv'], 2),
            'Total estimado: S/ '.number_format($totales['total'], 2),
            '',
            'Quedo atento a su confirmación. Gracias.',
        ]);
    }
}
