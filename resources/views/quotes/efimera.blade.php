@extends('layouts.app')

@section('title', 'Tu cotización')

@section('content')
    <div class="mx-auto max-w-3xl">
        <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-eyebrow">Cotización sin cuenta</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">
                    Tu enlace de WhatsApp está listo
                </h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    No necesitas RUC ni cuenta para cotizar. Ábrelo y envía el mensaje al distribuidor;
                    la conversación y el cierre del pedido ocurren fuera de Mayoro.
                </p>
            </div>
        </header>

        <section class="card mt-6" data-testid="cotizacion-efimera">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="section-eyebrow">Producto</p>
                    <h2 class="mt-1 break-words text-lg font-semibold text-slate-900">
                        {{ $producto->nombre }}
                    </h2>
                    <p class="m-0 mt-1 text-sm text-slate-600">
                        {{ $producto->presentacion }} &middot; {{ $cotizacion['unidades_por_bulto'] }} unidades por bulto
                    </p>
                </div>

                <span class="badge badge-brand shrink-0">Enlace efímero</span>
            </div>

            <dl class="mt-5 space-y-2 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-600">Precio por bulto</dt>
                    <dd class="m-0 font-semibold text-slate-900">
                        S/ {{ number_format((float) $cotizacion['precio_bulto'], 2) }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-600">Bultos solicitados</dt>
                    <dd class="m-0 font-semibold text-slate-900">{{ $cotizacion['cantidad_solicitada'] }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-600">Subtotal</dt>
                    <dd class="m-0 font-semibold text-slate-900">
                        S/ {{ number_format((float) $cotizacion['subtotal'], 2) }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-600">IGV (18%)</dt>
                    <dd class="m-0 font-semibold text-slate-900">
                        S/ {{ number_format((float) $cotizacion['igv'], 2) }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                    <dt class="font-semibold text-slate-800">Total estimado</dt>
                    <dd class="m-0 text-xl font-semibold text-brand-800">
                        S/ {{ number_format((float) $cotizacion['total'], 2) }}
                    </dd>
                </div>
            </dl>

            <div class="mt-6">
                @if (! empty($cotizacion['url_whatsapp']))
                    <a class="btn w-full bg-[#25d366] text-brand-950 hover:bg-[#1fbe5b]"
                       href="{{ $cotizacion['url_whatsapp'] }}" target="_blank" rel="noopener"
                       data-testid="cotizacion-efimera-whatsapp">
                        Abrir chat de WhatsApp
                    </a>
                @else
                    <div class="surface-danger" role="alert">
                        <p class="m-0">
                            El distribuidor no registra un número de WhatsApp válido. Escríbele a
                            <strong>{{ $producto->distribuidor->email_contacto ?? 'su correo de contacto' }}</strong>
                            e indica estas condiciones.
                        </p>
                    </div>

                    <a class="btn ghost mt-4 w-full" href="{{ route('suppliers.show', $producto->distribuidor_id) }}">
                        Ver ficha del distribuidor
                    </a>
                @endif
            </div>

            <p class="surface-note mt-4">
                Este enlace vive solo en tu sesión: no se guardó en la base de datos y no aparecerá en un
                historial. Si cierras el navegador tendrás que generarlo otra vez.
            </p>
        </section>

        <section class="card mt-4 border-brand-200 bg-brand-50" data-testid="cotizacion-efimera-registro">
            <h2 class="mt-0 text-lg font-semibold text-brand-900">¿Quieres conservar tus cotizaciones?</h2>
            <p class="mt-2 text-sm leading-6 text-brand-900">
                Crea una cuenta gratuita como <strong>Persona natural</strong>: no pedimos RUC para cotizar y
                tus solicitudes quedan en tu historial. Si algún día quieres vender, puedes convertirte en
                Empresario con la validación de tu RUC.
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a class="btn" href="{{ route('auth.register', ['returnUrl' => route('products.show', $producto->id)]) }}">
                    Crear cuenta para guardar historial
                </a>
                <a class="btn ghost" href="{{ route('quotes.create', ['producto' => $producto->id]) }}">
                    Cotizar otra cantidad
                </a>
                <a class="btn ghost" href="{{ route('products.index') }}">Seguir explorando</a>
            </div>
        </section>
    </div>
@endsection
