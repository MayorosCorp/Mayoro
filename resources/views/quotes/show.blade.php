@extends('layouts.app')

@section('title', 'Cotización #' . $quote->id)

@section('content')
    @php
        $estadoClases = match ($quote->estado) {
            'generada' => 'badge-brand',
            'aceptada' => 'badge-success',
            'rechazada' => 'badge-danger',
            default => 'badge-muted',
        };
    @endphp

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-eyebrow">Cotización B2B</p>
            <h1 class="mt-1 flex flex-wrap items-center gap-3 text-3xl font-semibold tracking-tight text-slate-900">
                Cotización #{{ $quote->id }}
                <span class="badge {{ $estadoClases }}">{{ ucfirst($quote->estado) }}</span>
            </h1>
            <p class="mt-2 text-sm text-slate-600">
                Registrada el {{ $quote->created_at?->format('d/m/Y H:i') ?? '-' }}.
                No es un pedido confirmado: la negociación se cierra por WhatsApp.
            </p>
        </div>

        <a class="btn ghost shrink-0" href="{{ route('quotes.index') }}">Volver al listado</a>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
        <section class="card" data-testid="cotizacion-detalle">
            <h2 class="mt-0 text-lg font-semibold text-slate-900">Detalle del pedido</h2>

            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 sm:col-span-2">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Producto</dt>
                    <dd class="m-0 mt-1 font-semibold text-slate-900">
                        {{ $quote->producto->nombre ?? 'Producto eliminado' }}
                    </dd>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Presentación</dt>
                    <dd class="m-0 mt-1 font-semibold text-slate-900">{{ $quote->producto->presentacion ?? '-' }}</dd>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Unidades por bulto</dt>
                    <dd class="m-0 mt-1 font-semibold text-slate-900">{{ $quote->unidades_por_bulto }}</dd>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Bodega solicitante</dt>
                    <dd class="m-0 mt-1 font-semibold text-slate-900">{{ $quote->bodega->razon_social ?? '-' }}</dd>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Distribuidor</dt>
                    <dd class="m-0 mt-1 font-semibold text-slate-900">{{ $quote->distribuidor->razon_social ?? '-' }}</dd>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Cantidad de bultos</dt>
                    <dd class="m-0 mt-1 font-semibold text-slate-900">
                        {{ $quote->cantidad_solicitada }}
                        <span class="text-xs font-normal text-slate-500">
                            (MOQ del producto: {{ $quote->producto->moq_cantidad_minima ?? '-' }})
                        </span>
                    </dd>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Precio por bulto</dt>
                    <dd class="m-0 mt-1 font-semibold text-slate-900">S/ {{ number_format((float) $quote->precio_bulto, 2) }}</dd>
                </div>
            </dl>
        </section>

        <aside class="space-y-4">
            <section class="card" data-testid="cotizacion-totales">
                <h2 class="mt-0 text-lg font-semibold text-slate-900">Resumen económico</h2>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-600">Precio unitario referencial</dt>
                        <dd class="m-0 font-medium text-slate-900">S/ {{ number_format((float) $quote->precio_unitario, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-600">Subtotal ({{ $quote->cantidad_solicitada }} bultos)</dt>
                        <dd class="m-0 font-semibold text-slate-900">S/ {{ number_format((float) $quote->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-600">IGV (18%)</dt>
                        <dd class="m-0 font-semibold text-slate-900">S/ {{ number_format((float) $quote->igv, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                        <dt class="font-semibold text-slate-800">Total estimado</dt>
                        <dd class="m-0 text-xl font-semibold text-brand-800">S/ {{ number_format((float) $quote->total, 2) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="card" data-testid="cotizacion-whatsapp">
                <h2 class="mt-0 text-lg font-semibold text-slate-900">Derivación por WhatsApp</h2>

                @if ($quote->url_whatsapp)
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        La cotización quedó registrada. El enlace hacia
                        <strong>{{ $quote->distribuidor->telefono_whatsapp ?? 'el distribuidor' }}</strong>
                        está listo: ábrelo y envía el mensaje desde tu WhatsApp.
                    </p>

                    <a class="btn mt-4 w-full bg-[#25d366] text-brand-950 hover:bg-[#1fbe5b]"
                       href="{{ $quote->url_whatsapp }}" target="_blank" rel="noopener">
                        Abrir chat de WhatsApp
                    </a>

                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm font-medium text-slate-600">
                            Ver mensaje generado (URL codificada)
                        </summary>
                        <p class="mt-2 break-all text-xs leading-5 text-slate-500">
                            {{ $quote->url_whatsapp }}
                        </p>
                    </details>
                @else
                    <div class="surface-danger mt-3">
                        <p class="m-0">
                            El distribuidor no registra un número de WhatsApp válido, por lo que no se pudo
                            generar el enlace. Contacta por correo a
                            <strong>{{ $quote->distribuidor->email_contacto ?? 'sin correo registrado' }}</strong>.
                        </p>
                    </div>

                    <a class="btn ghost mt-4 w-full" href="{{ route('suppliers.show', $quote->distribuidor_id) }}">
                        Ver ficha del distribuidor
                    </a>
                @endif

                <p class="surface-note mt-4">
                    Mayoro solo genera el enlace: no confirma la entrega del mensaje ni el pedido.
                </p>
            </section>
        </aside>
    </div>
@endsection
