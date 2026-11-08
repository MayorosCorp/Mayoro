@extends('layouts.app')

@section('title', $product->nombre)

@section('content')
    <nav class="mb-4 text-sm" aria-label="Migas de pan">
        <a href="{{ route('products.index') }}" class="font-medium text-brand-700">&larr; Volver al catálogo</a>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
        <section class="card p-0 overflow-hidden" data-testid="producto-ficha">
            <div class="aspect-[4/3] w-full bg-slate-100">
                @if ($product->imagen_url)
                    <img src="{{ Storage::disk('public')->url($product->imagen_url) }}"
                         alt="{{ $product->nombre }}" class="h-full w-full object-cover">
                @else
                    <div class="grid h-full w-full place-items-center text-sm font-medium uppercase tracking-wide text-slate-400">
                        Sin imagen disponible
                    </div>
                @endif
            </div>

            <div class="space-y-4 p-6">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge badge-brand">{{ $product->categoria }}</span>
                    @if ($product->esta_agotado)
                        <span class="badge badge-muted">{{ $product->etiqueta_estado_stock }}</span>
                    @else
                        <span class="badge badge-success">Disponible: {{ $product->stock_disponible }} bultos</span>
                    @endif
                </div>

                <div>
                    <h1 class="m-0 text-3xl font-semibold tracking-tight text-slate-900">
                        {{ $product->nombre }}
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ $product->presentacion }} &middot; {{ $product->unidades_por_bulto }} unidades por bulto
                    </p>
                </div>

                @if ($product->descripcion)
                    <p class="text-sm leading-6 text-slate-700">{{ $product->descripcion }}</p>
                @endif

                <p class="m-0 text-sm text-slate-600">
                    Publicado por
                    <a class="font-semibold" href="{{ route('suppliers.show', $product->distribuidor_id) }}">
                        {{ $product->distribuidor->razon_social ?? 'Distribuidor' }}
                    </a>
                </p>

                @if (Auth::check() && Auth::user()->rol === 'distribuidor' && $product->distribuidor_id === Auth::id())
                    <a class="btn ghost" href="{{ route('products.edit', $product->id) }}" data-testid="producto-editar">
                        Editar producto
                    </a>
                @endif
            </div>
        </section>

        <aside class="space-y-4">
            <section class="card" data-testid="producto-condiciones">
                <p class="section-eyebrow">Condiciones mayoristas</p>
                <p class="mt-2 flex items-baseline gap-1.5">
                    <span class="price-bulto text-3xl">S/ {{ number_format((float) $product->precio_bulto, 2) }}</span>
                    <span class="text-sm font-medium text-slate-500">/ bulto</span>
                </p>
                <p class="mt-1 text-sm text-slate-500">
                    Unitario referencial: S/ {{ number_format($product->precio_unitario_calculado, 2) }}
                </p>

                <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Bulto</dt>
                        <dd class="m-0 mt-1 font-semibold text-slate-900">{{ $product->unidades_por_bulto }} unidades</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">MOQ</dt>
                        <dd class="m-0 mt-1 font-semibold text-slate-900">{{ $product->moq_cantidad_minima }} bultos</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Stock</dt>
                        <dd class="m-0 mt-1 font-semibold text-slate-900">{{ $product->stock_disponible }} bultos</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Presentación</dt>
                        <dd class="m-0 mt-1 font-semibold text-slate-900">{{ $product->presentacion }}</dd>
                    </div>
                </dl>
            </section>

            <section class="card" data-testid="producto-desglose">
                <h2 class="mt-0 text-base font-semibold text-slate-900">Desglose por bulto</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-600">Precio por bulto sin IGV</dt>
                        <dd class="m-0 font-semibold text-slate-900">S/ {{ number_format((float) $product->precio_bulto, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-600">IGV (18%)</dt>
                        <dd class="m-0 font-semibold text-slate-900">S/ {{ number_format($igv, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-2">
                        <dt class="font-semibold text-slate-800">Total por bulto con IGV</dt>
                        <dd class="m-0 text-lg font-semibold text-brand-800">S/ {{ number_format($precioConIgv, 2) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="card" data-testid="producto-accion">
                @if ($product->esta_agotado)
                    <h2 class="mt-0 text-lg font-semibold text-slate-900">{{ $product->etiqueta_estado_stock }}</h2>

                    @if ($product->estado_stock === 'pausado')
                        <p class="mb-4 mt-2 text-sm leading-6 text-slate-600">
                            El distribuidor detuvo la publicación de este producto. Vuelve a contactarlo
                            para saber si volverá a estarlo.
                        </p>
                    @elseif ($product->estado_stock === 'stock_insuficiente')
                        <p class="mb-4 mt-2 text-sm leading-6 text-slate-600">
                            Solo quedan {{ $product->stock_disponible }} bultos y el pedido mínimo es de
                            {{ $product->moq_cantidad_minima }} bultos. Contacta al distribuidor para
                            confirmar una reposición.
                        </p>
                    @else
                        <p class="mb-4 mt-2 text-sm leading-6 text-slate-600">
                            Este producto agotó sus existencias por el momento.
                            Puedes contactar al distribuidor para conocer la fecha de reposición.
                        </p>
                    @endif

                    <a class="btn ghost w-full" href="{{ route('suppliers.show', $product->distribuidor_id) }}">
                        Ver catálogo del distribuidor
                    </a>
                @elseif (Auth::check() && Auth::user()->rol === 'bodega')
                    <h2 class="mt-0 text-lg font-semibold text-slate-900">Solicitar cotización</h2>
                    <p class="mb-4 mt-2 text-sm leading-6 text-slate-600">
                        El pedido mínimo es de {{ $product->moq_cantidad_minima }} bultos. Mayoro prepara
                        el resumen y tú lo envías al distribuidor por WhatsApp.
                    </p>
                    <a class="btn w-full" href="{{ route('quotes.create', ['producto' => $product->id]) }}"
                       data-testid="producto-cotizar">
                        Cotizar pedido
                    </a>
                @elseif (Auth::check())
                    <h2 class="mt-0 text-lg font-semibold text-slate-900">Acceso exclusivo para bodegas</h2>
                    <p class="mb-4 mt-2 text-sm leading-6 text-slate-600">
                        El flujo de cotización está habilitado para cuentas con rol bodega.
                    </p>
                    <a class="btn ghost w-full" href="{{ route('products.index') }}">Volver al catálogo</a>
                @else
                    {{-- Ficha CI-COD-13: el escenario exige modal informativo, no una tarjeta --}}
                    <h2 class="mt-0 text-lg font-semibold text-slate-900">Cotiza este producto en mayorista</h2>
                    <p class="mb-4 mt-2 text-sm leading-6 text-slate-600">
                        El pedido mínimo es de {{ $product->moq_cantidad_minima }} bultos.
                        Inicia sesión o crea tu cuenta de bodega para generar el resumen de WhatsApp.
                    </p>
                    <button class="btn w-full" type="button" data-modal-abrir="authModal">
                        Cotizar pedido
                    </button>
                @endif
            </section>
        </aside>
    </div>

    @unless (Auth::check())
        <x-auth-modal :return-url="route('products.show', $product->id)" />
    @endunless
@endsection
