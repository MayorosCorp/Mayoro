<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Models\Product;
use App\Models\Quote;
use App\Services\QuotationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function __construct(
        private QuotationService $quotation
    ) {}

    /**
     * Listado de cotizaciones segun el rol: la bodega ve las que solicito,
     * el distribuidor ve las que recibio.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $quotes = Quote::with(['producto', 'bodega', 'distribuidor'])
            ->where($user->rol === 'distribuidor' ? 'distribuidor_id' : 'bodega_id', $user->id)
            ->latest()
            ->paginate(12);

        return view('quotes.index', compact('quotes'));
    }

    /**
     * Formulario de cotizacion. Acepta ?producto=ID para cotizar desde el catalogo.
     */
    public function create(Request $request): View
    {
        $productos = Product::with('distribuidor')
            ->where('is_active', true)
            ->orderBy('nombre')
            ->get();

        $seleccionado = null;

        if ($request->integer('producto')) {
            $seleccionado = $productos->firstWhere('id', $request->integer('producto'));
        }

        return view('quotes.create', compact('productos', 'seleccionado'));
    }

    /**
     * Registra la cotizacion y genera el enlace codificado de WhatsApp (HU-04).
     */
    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $producto = Product::with('distribuidor')->findOrFail($request->integer('producto_id'));
        $cantidad = (int) $request->integer('cantidad_solicitada');

        $totales = $this->quotation->calcularTotales($producto->precio_unitario_calculado, $cantidad);
        $telefono = (string) $producto->distribuidor?->telefono_whatsapp;
        $mensaje = $this->quotation->resumirCotizacion($request->user(), $producto, $cantidad);

        $urlWhatsapp = $this->esTelefonoUtilizable($telefono)
            ? $this->quotation->generateWhatsAppLink($telefono, $mensaje)
            : null;

        $quote = Quote::create([
            'producto_id' => $producto->id,
            'bodega_id' => $request->user()->id,
            'distribuidor_id' => $producto->distribuidor_id,
            'cantidad_solicitada' => $cantidad,
            'precio_unitario' => $producto->precio_unitario_calculado,
            'subtotal' => $totales['subtotal'],
            'igv' => $totales['igv'],
            'total' => $totales['total'],
            'telefono_destino' => $telefono,
            'url_whatsapp' => $urlWhatsapp,
            'estado' => $urlWhatsapp !== null ? 'enviada' : 'pendiente',
        ]);

        if ($urlWhatsapp === null) {
            return redirect()
                ->route('quotes.show', $quote)
                ->with('error', 'El distribuidor no tiene un numero de WhatsApp valido. Contactalo por correo: '.$producto->distribuidor?->email_contacto);
        }

        return redirect()
            ->route('quotes.show', $quote)
            ->with('success', 'Cotizacion registrada. Envia el resumen al distribuidor por WhatsApp.');
    }

    public function show(Request $request, Quote $quote): View
    {
        $this->autorizarAcceso($request, $quote);

        $quote->load(['producto', 'bodega', 'distribuidor']);

        return view('quotes.show', compact('quote'));
    }

    /**
     * Una cotizacion solo es visible por la bodega solicitante o su distribuidor.
     */
    private function autorizarAcceso(Request $request, Quote $quote): void
    {
        $userId = $request->user()->id;

        abort_unless(
            in_array($userId, [$quote->bodega_id, $quote->distribuidor_id], true),
            403,
            'No esta autorizado para consultar esta cotizacion'
        );
    }

    /**
     * Contingencia HU-04: sin telefono valido se degrada a la via de correo.
     */
    private function esTelefonoUtilizable(string $telefono): bool
    {
        return preg_match('/^51\d{9}$/', preg_replace('/\D+/', '', $telefono) ?? '') === 1;
    }
}
