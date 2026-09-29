@extends('layouts.app')

@section('title', 'Catálogo Mayorista')

@section('content')
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
        <div>
            <h1 style="margin-bottom:.25rem;">Catálogo Mayorista</h1>
            <p style="color:#6b7280; margin:0; font-size:.95rem;">
                Explora precios por bulto, presentación y pedido mínimo (MOQ) de los distribuidores
                registrados. Los precios se muestran sin IGV; el impuesto se desglosa en cada producto.
            </p>
        </div>

        @if (Auth::check() && Auth::user()->rol === 'distribuidor')
            <a class="btn" href="{{ route('products.create') }}">+ Publicar Nuevo Producto</a>
        @endif
    </div>

    {{-- Ficha CI-COD-10: buscador por texto y filtro por categoría (HU-03) --}}
    <form method="GET" action="{{ route('products.index') }}" class="card" style="margin-top:1.5rem;">
        <div style="display:grid; grid-template-columns:2fr 1fr auto; gap:1rem; align-items:end;">
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
            <div style="display:flex; gap:.5rem;">
                <button class="btn" type="submit">Filtrar</button>
                @if ($busqueda !== '' || $categoria !== '')
                    <a class="btn ghost" href="{{ route('products.index') }}">Limpiar</a>
                @endif
            </div>
        </div>
    </form>

    <p style="color:#6b7280; font-size:.85rem; margin-top:1rem;">
        {{ $products->total() }} producto(s) encontrado(s)
        @if ($busqueda !== '') &middot; texto: <strong>{{ $busqueda }}</strong> @endif
        @if ($categoria !== '') &middot; categoría: <strong>{{ $categoria }}</strong> @endif
    </p>

    <div class="grid" style="margin-top:1rem;">
        @forelse ($products as $prod)
            <article class="card" style="display:flex; flex-direction:column; gap:.5rem; {{ $prod->esta_agotado ? 'opacity:.65;' : '' }}">
                @if ($prod->imagen_url)
                    <img src="{{ Storage::disk('public')->url($prod->imagen_url) }}" alt="{{ $prod->nombre }}"
                         style="width:100%; height:120px; object-fit:cover; border-radius:.375rem; border:1px solid #e5e7eb;">
                @else
                    <div style="width:100%; height:120px; border-radius:.375rem; background:#f3f4f6;
                                display:flex; align-items:center; justify-content:center; color:#9ca3af; font-size:.8rem;">
                        Sin imagen
                    </div>
                @endif

                <span style="font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:#6b7280;">
                    {{ $prod->categoria }}
                </span>

                <a href="{{ route('products.show', $prod->id) }}"
                   style="font-weight:700; font-size:1rem; text-decoration:none; color:#1f2937;">
                    {{ $prod->nombre }}
                </a>

                <span style="font-size:.82rem; color:#6b7280;">
                    {{ $prod->presentacion }} &middot; {{ $prod->unidades_por_bulto }} und. por bulto
                </span>

                <div>
                    <span style="font-size:1.25rem; font-weight:700; color:#12305c;">
                        S/ {{ number_format($prod->precio_unitario_calculado, 2) }}
                    </span>
                    <span style="display:block; font-size:.75rem; color:#6b7280;">
                        por unidad &middot; bulto S/ {{ number_format((float) $prod->precio_bulto, 2) }}
                    </span>
                    <span style="display:block; font-size:.75rem; color:#166534;">
                        IGV 18%: S/ {{ number_format($prod->precio_unitario_calculado * 0.18, 2) }}
                        &middot; total S/ {{ number_format($prod->precio_unitario_calculado * 1.18, 2) }}
                    </span>
                </div>

                <span style="font-size:.75rem;">
                    <span style="background:#fffbeb; color:#92400e; border:1px solid #fde68a;
                                 border-radius:999px; padding:.1rem .55rem;">
                        MOQ: {{ $prod->moq_cantidad_minima }} bultos
                    </span>
                </span>

                @if ($prod->esta_agotado)
                    {{-- La leyenda es la exigida por HU-03; el motivo se aclara aparte --}}
                    <span class="btn disabled" aria-disabled="true">Temporalmente sin stock</span>
                    @if ($prod->estado_stock === 'pausado')
                        <span style="font-size:.72rem; color:#9ca3af;">Pausado por el distribuidor</span>
                    @endif
                @elseif (Auth::check() && Auth::user()->rol === 'bodega')
                    <a class="btn" href="{{ route('quotes.create', ['producto' => $prod->id]) }}">Cotizar pedido</a>
                @elseif (Auth::check())
                    <span style="font-size:.8rem; color:#6b7280;">Solo bodegas cotizan</span>
                @else
                    {{-- Ficha CI-COD-13: cada tarjeta declara su propio returnUrl --}}
                    <button class="btn" type="button" data-modal-abrir="authModal"
                            data-return-url="{{ route('products.show', $prod->id) }}">
                        Ingresar para cotizar
                    </button>
                @endif

                <span style="font-size:.75rem; color:#6b7280;">
                    {{ $prod->distribuidor->razon_social ?? 'Distribuidor' }}
                </span>
            </article>
        @empty
            <div class="card">
                <h3 style="margin-top:0;">No encontramos productos con esos criterios</h3>
                <p style="color:#6b7280; margin:0;">
                    Prueba con otro término de búsqueda o
                    <a href="{{ route('products.index') }}">muestra todo el catálogo</a>.
                </p>
            </div>
        @endforelse
    </div>

    @if ($products->hasPages())
        <div style="margin-top:2rem;">{{ $products->links() }}</div>
    @endif

    @guest
        <x-auth-modal />
    @endguest
@endsection
