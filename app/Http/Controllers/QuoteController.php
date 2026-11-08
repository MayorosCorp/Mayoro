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
     * Historial de cotizaciones: la Persona Natural ve las que genero y el
     * Empresario las que recibio. Ambas caras son el mismo registro.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $quotes = Quote::with(['producto', 'bodega', 'distribuidor'])
            ->where($user->esEmpresario() ? 'distribuidor_id' : 'bodega_id', $user->id)
            ->latest()
            ->paginate(12);

        return view('quotes.index', compact('quotes'));
    }

    /**
     * Formulario de cotizacion. Acepta ?producto=ID para cotizar desde el catalogo.
     */
    public function create(Request $request): View
    {
        // Solo se ofrece lo que el servidor aceptaria: producto publicado y con
        // stock suficiente para cubrir su propio MOQ (RN-05).
        $productos = Product::with('distribuidor')
            ->where('is_active', true)
            ->where('stock_disponible', '>=', 1)
            ->whereColumn('stock_disponible', '>=', 'moq_cantidad_minima')
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
     *
     * RN-01: un visitante sin cuenta tambien cotiza. Su solicitud no se
     * persiste: se guarda en sesion y se muestra como enlace efimero, porque no
     * hay identidad verificada a la que atribuir un historial.
     */
    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $producto = Product::with('distribuidor')->findOrFail($request->integer('producto_id'));
        $cantidad = (int) $request->integer('cantidad_solicitada');

        // Blindaje adicional ante una carrera entre la validacion y el guardado:
        // el stock y la publicacion se vuelven a comprobar aqui.
        if ($producto->esta_agotado || $cantidad > $producto->stock_disponible) {
            return back()
                ->withInput()
                ->withErrors([
                    'cantidad_solicitada' => 'El producto cambio de disponibilidad mientras generabas la cotizacion. Revisa el catalogo antes de intentarlo de nuevo.',
                ]);
        }

        $precioBulto = (float) $producto->precio_bulto;
        $totales = $this->quotation->calcularTotales($precioBulto, $cantidad);
        $telefono = (string) $producto->distribuidor?->telefono_whatsapp;
        $solicitante = $request->user();
        $mensaje = $this->quotation->resumirCotizacion($solicitante, $producto, $cantidad);

        $urlWhatsapp = $this->esTelefonoUtilizable($telefono)
            ? $this->quotation->generateWhatsAppLink($telefono, $mensaje)
            : null;

        if ($solicitante === null) {
            $request->session()->put('cotizacion_efimera', [
                'producto_id' => $producto->id,
                'cantidad_solicitada' => $cantidad,
                'precio_bulto' => $precioBulto,
                'unidades_por_bulto' => $producto->unidades_por_bulto,
                'subtotal' => $totales['subtotal'],
                'igv' => $totales['igv'],
                'total' => $totales['total'],
                'telefono_destino' => $telefono,
                'url_whatsapp' => $urlWhatsapp,
                'generada_en' => now()->toIso8601String(),
            ]);

            return redirect()->route('quotes.efimera');
        }

        $quote = Quote::create([
            'producto_id' => $producto->id,
            'bodega_id' => $solicitante->id,
            'distribuidor_id' => $producto->distribuidor_id,
            'cantidad_solicitada' => $cantidad,
            // Snapshot del bulto: la cotizacion queda congelada con las
            // condiciones vigentes al generarla.
            'precio_bulto' => $precioBulto,
            'unidades_por_bulto' => $producto->unidades_por_bulto,
            'precio_unitario' => $producto->precio_unitario_calculado,
            'subtotal' => $totales['subtotal'],
            'igv' => $totales['igv'],
            'total' => $totales['total'],
            'telefono_destino' => $telefono,
            'url_whatsapp' => $urlWhatsapp,
            // Mayoro genera el enlace, no entrega el mensaje: "generada" es el
            // unico estado que puede afirmar sin inventar datos.
            'estado' => 'generada',
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

    /**
     * Enlace efimero del visitante sin cuenta: vive en su sesion, no en la base
     * de datos. Al no haber usuario no se puede prometer historial ni autoría.
     */
    public function efimera(Request $request): View|RedirectResponse
    {
        $datos = $request->session()->get('cotizacion_efimera');

        if (! is_array($datos)) {
            return redirect()
                ->route('quotes.create')
                ->with('error', 'Tu cotizacion efimera ya no esta disponible. Genera una nueva.');
        }

        $producto = Product::with('distribuidor')->find($datos['producto_id'] ?? null);

        if ($producto === null) {
            $request->session()->forget('cotizacion_efimera');

            return redirect()
                ->route('quotes.create')
                ->with('error', 'El producto de esa cotizacion ya no esta disponible en el catalogo.');
        }

        return view('quotes.efimera', [
            'cotizacion' => $datos,
            'producto' => $producto,
        ]);
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
