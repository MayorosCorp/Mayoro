@extends('layouts.app')

@section('title', 'Cotización #' . $quote->id)

@section('content')
    <div style="display:flex; align-items:center; justify-content:space-between;">
        <h1>Cotización #{{ $quote->id }}</h1>
        <a class="btn ghost" href="{{ route('quotes.index') }}">Volver al listado</a>
    </div>

    <div class="card" style="margin-top:1.5rem;">
        <h2 style="margin-top:0; font-size:1.2rem;">Detalle del pedido</h2>

        <table>
            <tbody>
                <tr>
                    <th style="width:35%;">Producto</th>
                    <td>{{ $quote->producto->nombre ?? 'Producto eliminado' }}</td>
                </tr>
                <tr>
                    <th>Presentación</th>
                    <td>{{ $quote->producto->presentacion ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Bodega solicitante</th>
                    <td>{{ $quote->bodega->razon_social ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Distribuidor</th>
                    <td>{{ $quote->distribuidor->razon_social ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Cantidad de bultos</th>
                    <td>{{ $quote->cantidad_solicitada }}
                        (MOQ del producto: {{ $quote->producto->moq_cantidad_minima ?? '-' }})</td>
                </tr>
                <tr>
                    <th>Precio unitario referencial</th>
                    <td>S/ {{ number_format((float) $quote->precio_unitario, 2) }}</td>
                </tr>
                <tr>
                    <th>Subtotal</th>
                    <td>S/ {{ number_format((float) $quote->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <th>IGV (18%)</th>
                    <td>S/ {{ number_format((float) $quote->igv, 2) }}</td>
                </tr>
                <tr>
                    <th>Total estimado</th>
                    <td><strong style="font-size:1.1rem;">S/ {{ number_format((float) $quote->total, 2) }}</strong></td>
                </tr>
                <tr>
                    <th>Estado</th>
                    <td>{{ ucfirst($quote->estado) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top:1.5rem;">
        <h2 style="margin-top:0; font-size:1.2rem;">Derivación por WhatsApp</h2>

        @if ($quote->url_whatsapp)
            <p>La cotización quedó registrada y el enlace oficial hacia
                <strong>{{ $quote->distribuidor->telefono_whatsapp ?? 'el distribuidor' }}</strong> está listo.</p>

            <p style="margin-top:1rem;">
                <a class="btn" style="background:#25d366;"
                   href="{{ $quote->url_whatsapp }}" target="_blank" rel="noopener">
                    Abrir chat de WhatsApp
                </a>
                <a class="btn ghost" href="{{ route('quotes.index') }}">Volver</a>
            </p>

            <details style="margin-top:1.25rem;">
                <summary style="cursor:pointer; color:#6b7280; font-size:.9rem;">
                    Ver mensaje generado (URL codificada)
                </summary>
                <p style="word-break:break-all; font-size:.8rem; color:#6b7280; margin-top:.5rem;">
                    {{ $quote->url_whatsapp }}
                </p>
            </details>
        @else
            <div class="flash error" style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca; padding:.75rem 1rem; border-radius:.375rem;">
                Contingencia: el distribuidor no registra un número de WhatsApp válido,
                por lo que no se pudo generar el enlace. Contacta por correo a
                <strong>{{ $quote->distribuidor->email_contacto ?? 'sin correo registrado' }}</strong>.
            </div>
        @endif
    </div>
@endsection
