@extends('layouts.app')

@section('title', $supplier->razon_social)

@section('content')
    <nav class="mb-4 text-sm" aria-label="Migas de pan">
        <a href="{{ route('suppliers.index') }}" class="font-medium text-brand-700">&larr; Volver a distribuidores</a>
    </nav>

    <section class="card" data-testid="distribuidor-ficha">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <span class="badge badge-muted">RUC {{ $supplier->ruc_empresa }}</span>
                <h1 class="mb-1 mt-3 break-words text-3xl font-semibold tracking-tight text-slate-900">
                    {{ $supplier->razon_social }}
                </h1>
                <p class="m-0 text-sm text-slate-600">
                    <span class="font-semibold text-slate-800">{{ $supplier->productos_count }}</span>
                    producto(s) activo(s) en el catálogo mayorista
                </p>
            </div>

            <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                @if ($supplier->is_premium)
                    <span class="badge badge-accent shrink-0">Distribuidor Premium</span>
                @endif

                @if ($supplier->micrositio_url)
                    <a class="btn" href="{{ $supplier->micrositio_url }}"
                       data-testid="distribuidor-micrositio">
                        Ver micrositio
                    </a>
                @endif
            </div>
        </div>
    </section>

    <div class="mt-8">
        <p class="section-eyebrow">Catálogo del distribuidor</p>
        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
            Productos publicados
        </h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
            Puedes cotizar sin cuenta ni RUC: Mayoro prepara el enlace de WhatsApp y tú envías el mensaje.
            Con una cuenta gratuita, además, conservas el historial.
        </p>
    </div>

    <div class="card-grid mt-4">
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
                <h2 class="mt-0 text-lg font-semibold text-slate-900">Este distribuidor aún no tiene productos activos</h2>
                <p class="mb-0 text-sm leading-6 text-slate-600">
                    Vuelve pronto o revisa el
                    <a href="{{ route('products.index') }}">catálogo completo</a>.
                </p>
            </div>
        @endforelse
    </div>

    @if ($productos->hasPages())
        <div class="mt-8">{{ $productos->links() }}</div>
    @endif
@endsection
