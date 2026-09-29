@extends('layouts.app')

@section('title', $supplier->razon_social)

@section('content')
    <a href="{{ route('suppliers.index') }}" style="font-size:.9rem;">&larr; Volver a distribuidores</a>

    <div class="card" style="margin-top:1rem;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div>
                <span style="font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:#6b7280;">
                    RUC {{ $supplier->ruc_empresa }}
                </span>
                <h1 style="margin:.25rem 0 .4rem 0;">{{ $supplier->razon_social }}</h1>
                <p style="color:#6b7280; margin:0; font-size:.9rem;">
                    {{ $supplier->productos_count }} producto(s) activo(s) en el catálogo mayorista
                </p>
            </div>

            @if ($supplier->is_premium)
                <span style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;
                             border-radius:999px; padding:.2rem .7rem; font-size:.8rem; font-weight:600;">
                    Distribuidor Premium
                </span>
            @endif
        </div>
    </div>

    <h2 style="font-size:1.2rem; margin-top:2rem;">Catálogo published por este distribuidor</h2>
    <p style="color:#6b7280; margin:.25rem 0 0 0; font-size:.9rem;">
        Para cotizar un producto necesitas una cuenta de bodega registrada.
    </p>

    <div class="grid" style="margin-top:1rem;">
        @forelse ($productos as $producto)
            <article class="card" style="display:flex; flex-direction:column; gap:.45rem; {{ $producto->esta_agotado ? 'opacity:.65;' : '' }}">
                @if ($producto->imagen_url)
                    <img src="{{ Storage::disk('public')->url($producto->imagen_url) }}" alt="{{ $producto->nombre }}"
                         style="width:100%; height:110px; object-fit:cover; border-radius:.375rem; border:1px solid #e5e7eb;">
                @endif

                <span style="font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:#6b7280;">
                    {{ $producto->categoria }}
                </span>

                <a href="{{ route('products.show', $producto->id) }}"
                   style="font-weight:700; text-decoration:none; color:#1f2937;">
                    {{ $producto->nombre }}
                </a>

                <span style="font-size:.82rem; color:#6b7280;">
                    {{ $producto->presentacion }} &middot; {{ $producto->unidades_por_bulto }} und. por bulto
                </span>

                <span style="font-size:1.15rem; font-weight:700; color:#12305c;">
                    S/ {{ number_format($producto->precio_unitario_calculado, 2) }}
                    <span style="display:block; font-size:.75rem; font-weight:500; color:#6b7280;">
                        por unidad &middot; IGV 18% S/ {{ number_format($producto->precio_unitario_calculado * 0.18, 2) }}
                    </span>
                </span>

                <span style="font-size:.75rem;">
                    <span style="background:#fffbeb; color:#92400e; border:1px solid #fde68a;
                                 border-radius:999px; padding:.1rem .55rem;">
                        MOQ: {{ $producto->moq_cantidad_minima }} bultos
                    </span>
                </span>

                @if ($producto->esta_agotado)
                    <span class="btn disabled" aria-disabled="true">Temporalmente sin stock</span>
                @elseif (Auth::check() && Auth::user()->rol === 'bodega')
                    <a class="btn" href="{{ route('quotes.create', ['producto' => $producto->id]) }}">Cotizar pedido</a>
                @else
                    <a class="btn" href="{{ route('auth.login', ['returnUrl' => route('products.show', $producto->id)]) }}">
                        Ingresar para cotizar
                    </a>
                @endif
            </article>
        @empty
            <div class="card">
                <h3 style="margin-top:0;">Este distribuidor aún no tiene productos activos</h3>
                <p style="color:#6b7280; margin:0;">
                    Vuelve pronto o revisa el
                    <a href="{{ route('products.index') }}">catálogo completo</a>.
                </p>
            </div>
        @endforelse
    </div>

    @if ($productos->hasPages())
        <div style="margin-top:2rem;">{{ $productos->links() }}</div>
    @endif
@endsection
