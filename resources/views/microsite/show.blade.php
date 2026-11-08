@extends('layouts.app')

@section('title', $empresario->razon_social)
@section('meta_description', \Illuminate\Support\Str::limit($empresario->descripcion ?: 'Catálogo mayorista de '.$empresario->razon_social.' con precio por bulto, pedido mínimo y cotización directa por WhatsApp.', 155))

@section('content')
    @php
        $whatsapp = preg_replace('/\D/', '', (string) $empresario->telefono_whatsapp);
        $mensajeWhatsApp = rawurlencode('Hola '.$empresario->razon_social.', vi su micrositio en Mayoro y quiero consultar por su catálogo mayorista.');
    @endphp

    <nav class="mb-4 text-sm" aria-label="Migas de pan">
        <a href="{{ route('suppliers.index') }}" class="font-medium text-brand-700">&larr; Volver a distribuidores</a>
    </nav>

    <section class="overflow-hidden rounded-2xl p-6 shadow-card sm:p-10"
             style="background-color: {{ $empresario->color_primario }}; color: {{ $empresario->color_primario_texto }};"
             data-testid="micrositio-hero">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
                          style="border-color: currentColor;">Empresario validado</span>

                    @if ($empresario->is_premium)
                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
                              style="border-color: currentColor;">Distribuidor Premium</span>
                    @endif
                </div>

                <h1 class="mb-2 mt-4 break-words text-3xl font-semibold tracking-tight sm:text-4xl">
                    {{ $empresario->razon_social }}
                </h1>

                @if ($empresario->rubro)
                    <p class="m-0 text-sm font-medium opacity-90">{{ $empresario->rubro }}</p>
                @endif

                @if ($empresario->descripcion)
                    <p class="mt-4 max-w-2xl whitespace-pre-line text-sm leading-6 opacity-95">{{ $empresario->descripcion }}</p>
                @endif

                <p class="mt-4 text-sm opacity-90">
                    <span class="font-semibold">{{ $empresario->productos_count }}</span>
                    producto(s) activo(s) en su catálogo mayorista
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap gap-2">
                @if ($whatsapp !== '')
                    <a class="inline-flex min-h-11 items-center justify-center rounded-lg border-2 px-4 py-2.5 text-sm font-semibold no-underline transition hover:opacity-90"
                       style="border-color: currentColor; color: inherit;"
                       href="https://wa.me/{{ $whatsapp }}?text={{ $mensajeWhatsApp }}"
                       target="_blank" rel="noopener"
                       data-testid="micrositio-whatsapp">
                        Escribir por WhatsApp
                    </a>
                @endif

                @auth
                    @if (auth()->id() === $empresario->id)
                        <a class="inline-flex min-h-11 items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold no-underline transition hover:opacity-90"
                           style="background-color: {{ $empresario->color_primario_texto }}; color: {{ $empresario->color_primario }};"
                           href="{{ route('microsite.edit') }}"
                           data-testid="micrositio-editar">
                            Editar mi empresa
                        </a>
                    @endif
                @endauth
            </div>
        </div>

        <dl class="mt-8 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
            @if ($empresario->ruc_empresa)
                <div class="rounded-lg border p-3" style="border-color: color-mix(in srgb, currentColor 35%, transparent);">
                    <dt class="text-xs font-semibold uppercase tracking-wide opacity-80">RUC</dt>
                    <dd class="m-0 mt-1 font-semibold">{{ $empresario->ruc_empresa }}</dd>
                </div>
            @endif

            <div class="rounded-lg border p-3" style="border-color: color-mix(in srgb, currentColor 35%, transparent);">
                <dt class="text-xs font-semibold uppercase tracking-wide opacity-80">Horario</dt>
                <dd class="m-0 mt-1 font-semibold">{{ $empresario->horario_atencion ?: 'Coordinar por WhatsApp' }}</dd>
            </div>

            <div class="rounded-lg border p-3" style="border-color: color-mix(in srgb, currentColor 35%, transparent);">
                <dt class="text-xs font-semibold uppercase tracking-wide opacity-80">Dirección</dt>
                <dd class="m-0 mt-1 font-semibold">{{ $empresario->direccion ?: 'Atención coordinada' }}</dd>
            </div>

            <div class="rounded-lg border p-3" style="border-color: color-mix(in srgb, currentColor 35%, transparent);">
                <dt class="text-xs font-semibold uppercase tracking-wide opacity-80">WhatsApp</dt>
                <dd class="m-0 mt-1 font-semibold">{{ $empresario->telefono_whatsapp ?: 'No publicado' }}</dd>
            </div>
        </dl>
    </section>

    <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-eyebrow">Catálogo del micrositio</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
                Productos de {{ $empresario->razon_social }}
            </h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Cotiza sin cuenta ni RUC: Mayoro prepara el resumen y tú lo envías al distribuidor por WhatsApp.
            </p>
        </div>

        <form method="GET" action="{{ route('microsite.show', $empresario->slug) }}"
              class="flex w-full gap-2 sm:w-auto" role="search">
            <div class="min-w-0 flex-1 sm:w-64">
                <label for="q" class="sr-only">Buscar en este micrositio</label>
                <input id="q" type="search" name="q" value="{{ $busqueda }}"
                       placeholder="Buscar en este catálogo...">
            </div>
            <button class="btn" type="submit">Buscar</button>
            @if ($busqueda !== '')
                <a class="btn ghost" href="{{ route('microsite.show', $empresario->slug) }}">Limpiar</a>
            @endif
        </form>
    </div>

    @if ($busqueda !== '')
        <p class="mt-4 text-sm text-slate-600">
            <span class="font-semibold text-slate-800">{{ $productos->total() }}</span>
            resultado(s) para «{{ $busqueda }}».
        </p>
    @endif

    <div class="card-grid mt-4" data-testid="micrositio-catalogo">
        @forelse ($productos as $producto)
            <article class="card card-hover flex flex-col gap-3 {{ $producto->esta_agotado ? 'bg-slate-50/80' : '' }}"
                     data-testid="producto-card">
                <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                    @if ($producto->imagen_url)
                        <img src="{{ Storage::disk('public')->url($producto->imagen_url) }}" alt="{{ $producto->nombre }}"
                             class="h-40 w-full object-cover" loading="lazy">
                    @else
                        <div class="grid h-40 w-full place-items-center text-xs font-medium uppercase tracking-wide text-slate-400">
                            Sin imagen
                        </div>
                    @endif

                    <span class="absolute left-2 top-2 badge badge-brand bg-white/95">{{ $producto->categoria }}</span>
                </div>

                <div class="flex flex-1 flex-col gap-2">
                    <a href="{{ route('products.show', $producto->id) }}"
                       class="break-words text-base font-semibold leading-snug text-slate-900 no-underline hover:text-brand-700">
                        {{ $producto->nombre }}
                    </a>

                    <p class="m-0 text-xs leading-5 text-slate-500">
                        {{ $producto->presentacion }} &middot; {{ $producto->unidades_por_bulto }} und. por bulto
                    </p>

                    <div class="mt-1">
                        <p class="m-0 flex items-baseline gap-1.5">
                            <span class="price-bulto">S/ {{ number_format((float) $producto->precio_bulto, 2) }}</span>
                            <span class="text-xs font-medium text-slate-500">/ bulto</span>
                        </p>
                        <p class="m-0 text-xs text-slate-500">
                            Unitario referencial: S/ {{ number_format($producto->precio_unitario_calculado, 2) }}
                        </p>
                        @if ($producto->tramosPrecio->isNotEmpty())
                            @php $mejorTramo = $producto->tramosPrecio->last(); @endphp
                            <p class="m-0 text-xs font-medium text-emerald-700" data-testid="producto-tramo">
                                Desde {{ $mejorTramo->cantidad_minima }} bultos:
                                S/ {{ number_format((float) $mejorTramo->precio_bulto, 2) }} / bulto
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <span class="badge badge-accent">MOQ: {{ $producto->moq_cantidad_minima }} bultos</span>

                        @if ($producto->esta_agotado)
                            <span class="badge badge-muted" data-testid="producto-estado">{{ $producto->etiqueta_estado_stock }}</span>
                        @else
                            <span class="badge badge-success" data-testid="producto-estado">
                                Disponible: {{ $producto->stock_disponible }} bultos
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mt-auto">
                    @if ($producto->esta_agotado)
                        <span class="btn disabled w-full" aria-disabled="true" data-testid="producto-sin-stock">
                            Temporalmente sin stock
                        </span>
                    @elseif (! Auth::check() || Auth::user()->esPersonaNatural())
                        <a class="btn w-full" href="{{ route('quotes.create', ['producto' => $producto->id]) }}"
                           data-testid="producto-cotizar">
                            Cotizar pedido
                        </a>
                    @else
                        <a class="btn ghost w-full" href="{{ route('products.show', $producto->id) }}">
                            Ver detalle
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="card col-span-full">
                <h2 class="mt-0 text-lg font-semibold text-slate-900">
                    @if ($busqueda !== '')
                        Sin resultados dentro de este micrositio
                    @else
                        Este micrositio aún no tiene productos activos
                    @endif
                </h2>
                <p class="mb-0 text-sm leading-6 text-slate-600">
                    @if ($busqueda !== '')
                        Prueba con otro nombre o
                        <a href="{{ route('microsite.show', $empresario->slug) }}">mira el catálogo completo</a>.
                    @else
                        Vuelve pronto o revisa el
                        <a href="{{ route('products.index') }}">catálogo general de Mayoro</a>.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    @if ($productos->hasPages())
        <div class="mt-8">{{ $productos->links() }}</div>
    @endif
@endsection
