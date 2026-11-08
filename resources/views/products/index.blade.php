@extends('layouts.app')

@section('title', 'Catálogo mayorista')

@section('content')
    <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-eyebrow">Catálogo público</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                Catálogo mayorista
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Precios por bulto, unidades por empaque y pedido mínimo (MOQ) de los distribuidores
                registrados. Los importes se muestran sin IGV y el impuesto se desglosa en cada producto.
            </p>
        </div>

        @if (Auth::check() && Auth::user()->rol === 'distribuidor')
            <a class="btn accent shrink-0" href="{{ route('products.create') }}" data-testid="catalogo-publicar">
                + Publicar Nuevo Producto
            </a>
        @endif
    </section>

    {{-- Ficha CI-COD-10: buscador por texto y filtro por categoría (HU-03) --}}
    <form method="GET" action="{{ route('products.index') }}"
          class="card mt-6"
          data-testid="catalogo-filtros">
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-[2fr_1fr_auto]">
            <div>
                <label for="q">Buscar producto</label>
                <input id="q" type="search" name="q" value="{{ $busqueda }}"
                       placeholder="Nombre, descripción, presentación o distribuidor...">
            </div>
            <div>
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $opcion)
                        <option value="{{ $opcion }}" @selected($categoria === $opcion)>{{ $opcion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button class="btn" type="submit">Filtrar</button>
                @if ($busqueda !== '' || $categoria !== '')
                    <a class="btn ghost" href="{{ route('products.index') }}">Limpiar</a>
                @endif
            </div>
        </div>
    </form>

    <div class="mt-5 flex flex-wrap items-center gap-2 text-sm text-slate-600">
        <span class="font-semibold text-slate-800">{{ $products->total() }}</span>
        <span>producto(s) encontrado(s)</span>

        @if ($busqueda !== '')
            <span class="badge badge-brand">texto: {{ $busqueda }}</span>
        @endif

        @if ($categoria !== '')
            <span class="badge badge-brand">categoría: {{ $categoria }}</span>
        @endif
    </div>

    <div class="card-grid mt-4">
        @forelse ($products as $prod)
            <article class="card card-hover flex flex-col gap-3 {{ $prod->esta_agotado ? 'bg-slate-50/80' : '' }}"
                     data-testid="producto-card">
                <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                    @if ($prod->imagen_url)
                        <img src="{{ Storage::disk('public')->url($prod->imagen_url) }}" alt="{{ $prod->nombre }}"
                             class="h-40 w-full object-cover" loading="lazy">
                    @else
                        <div class="grid h-40 w-full place-items-center text-xs font-medium uppercase tracking-wide text-slate-400">
                            Sin imagen
                        </div>
                    @endif

                    <span class="absolute left-2 top-2 badge badge-brand bg-white/95">{{ $prod->categoria }}</span>
                </div>

                <div class="flex flex-1 flex-col gap-2">
                    <a href="{{ route('products.show', $prod->id) }}"
                       class="text-base font-semibold leading-snug text-slate-900 no-underline hover:text-brand-700">
                        {{ $prod->nombre }}
                    </a>

                    <p class="m-0 text-xs leading-5 text-slate-500">
                        {{ $prod->presentacion }} &middot; {{ $prod->unidades_por_bulto }} und. por bulto
                    </p>

                    <div class="mt-1">
                        <p class="m-0 flex items-baseline gap-1.5">
                            <span class="price-bulto">S/ {{ number_format((float) $prod->precio_bulto, 2) }}</span>
                            <span class="text-xs font-medium text-slate-500">/ bulto</span>
                        </p>
                        <p class="m-0 text-xs text-slate-500">
                            Unitario referencial: S/ {{ number_format($prod->precio_unitario_calculado, 2) }}
                        </p>
                        <p class="m-0 text-xs font-medium text-emerald-700">
                            IGV 18%: S/ {{ number_format((float) $prod->precio_bulto * 0.18, 2) }}
                            &middot; total S/ {{ number_format((float) $prod->precio_bulto * 1.18, 2) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <span class="badge badge-accent">MOQ: {{ $prod->moq_cantidad_minima }} bultos</span>

                        @if ($prod->esta_agotado)
                            <span class="badge badge-muted" data-testid="producto-estado">{{ $prod->etiqueta_estado_stock }}</span>
                        @else
                            <span class="badge badge-success" data-testid="producto-estado">
                                Disponible: {{ $prod->stock_disponible }} bultos
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mt-auto space-y-2">
                    @if ($prod->esta_agotado)
                        {{-- La leyenda es la exigida por HU-03; el motivo se aclara aparte --}}
                        <span class="btn disabled w-full" aria-disabled="true" data-testid="producto-sin-stock">
                            Temporalmente sin stock
                        </span>
                        @if ($prod->estado_stock === 'pausado')
                            <p class="m-0 text-center text-xs text-slate-500">Pausado por el distribuidor</p>
                        @elseif ($prod->estado_stock === 'stock_insuficiente')
                            <p class="m-0 text-center text-xs text-slate-500">Stock insuficiente para el pedido mínimo</p>
                        @endif
                    @elseif (Auth::check() && Auth::user()->rol === 'bodega')
                        <a class="btn w-full" href="{{ route('quotes.create', ['producto' => $prod->id]) }}"
                           data-testid="producto-cotizar">
                            Cotizar pedido
                        </a>
                    @elseif (Auth::check())
                        <a class="btn ghost w-full" href="{{ route('products.show', $prod->id) }}">
                            Ver detalle
                        </a>
                    @else
                        {{-- Ficha CI-COD-13: cada tarjeta declara su propio returnUrl --}}
                        <button class="btn w-full" type="button" data-modal-abrir="authModal"
                                data-return-url="{{ route('products.show', $prod->id) }}">
                            Ingresar para cotizar
                        </button>
                    @endif

                    @if (Auth::check() && Auth::user()->rol === 'distribuidor' && $prod->distribuidor_id === Auth::id())
                        <a class="btn ghost w-full" href="{{ route('products.edit', $prod->id) }}">Editar producto</a>
                    @endif

                    <p class="m-0 text-center text-xs text-slate-500">
                        {{ $prod->distribuidor->razon_social ?? 'Distribuidor' }}
                    </p>
                </div>
            </article>
        @empty
            <div class="card col-span-full">
                <h2 class="mt-0 text-lg font-semibold">No encontramos productos con esos criterios</h2>
                <p class="mb-0 text-sm text-slate-600">
                    Prueba con otro término de búsqueda o
                    <a href="{{ route('products.index') }}">muestra todo el catálogo</a>.
                </p>
            </div>
        @endforelse
    </div>

    @if ($products->hasPages())
        <div class="mt-8">{{ $products->links() }}</div>
    @endif

    @guest
        <x-auth-modal />
    @endguest
@endsection
