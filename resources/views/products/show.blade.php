@extends('layouts.app')

@section('title', $product->nombre)

@section('content')
    <a href="{{ route('products.index') }}" style="font-size:.9rem;">&larr; Volver al catálogo</a>

    <div class="card" style="margin-top:1rem;">
        <div style="display:flex; gap:1.5rem; align-items:flex-start; flex-wrap:wrap;">
            @if ($product->imagen_url)
                <img src="{{ Storage::disk('public')->url($product->imagen_url) }}" alt="{{ $product->nombre }}"
                     style="max-width:280px; width:100%; border-radius:.5rem; border:1px solid #e5e7eb;">
            @endif

            <div style="flex:1; min-width:260px;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <span style="font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:#6b7280;">
                            {{ $product->categoria }}
                        </span>
                        <h1 style="margin:.25rem 0 .5rem 0;">{{ $product->nombre }}</h1>
                        <p style="color:#6b7280; margin:0; font-size:.9rem;">
                            {{ $product->presentacion }} &middot; {{ $product->unidades_por_bulto }} unidades por bulto
                        </p>
                    </div>

                    @if ($product->esta_agotado)
                        <span style="background:#f9fafb; color:#6b7280; border:1px solid #d1d5db;
                                     border-radius:999px; padding:.2rem .7rem; font-size:.8rem; font-weight:600;">
                            {{ $product->etiqueta_estado_stock }}
                        </span>
                    @else
                        <span style="background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0;
                                     border-radius:999px; padding:.2rem .7rem; font-size:.8rem; font-weight:600;">
                            Disponible
                        </span>
                    @endif
                </div>

                @if ($product->descripcion)
                    <p style="margin-top:1.25rem; font-size:.95rem;">{{ $product->descripcion }}</p>
                @endif
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1rem;">
        <div class="card">
            <h2 style="margin-top:0; font-size:1.1rem;">Condiciones mayoristas</h2>
            <table>
                <tbody>
                    <tr>
                        <th>Precio por bulto</th>
                        <td><strong>S/ {{ number_format((float) $product->precio_bulto, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <th>Unidades por bulto</th>
                        <td>{{ $product->unidades_por_bulto }}</td>
                    </tr>
                    <tr>
                        <th>Pedido mínimo (MOQ)</th>
                        <td>{{ $product->moq_cantidad_minima }} bultos</td>
                    </tr>
                    <tr>
                        <th>Stock disponible</th>
                        <td>{{ $product->stock_disponible }} bultos</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2 style="margin-top:0; font-size:1.1rem;">Precio referencial</h2>
            <table>
                <tbody>
                    <tr>
                        <th>Precio unitario sin IGV</th>
                        <td>S/ {{ number_format($product->precio_unitario_calculado, 2) }}</td>
                    </tr>
                    <tr>
                        <th>IGV (18%)</th>
                        <td>S/ {{ number_format($igv, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Precio unitario con IGV</th>
                        <td><strong>S/ {{ number_format($precioConIgv, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <th>Distribuidor</th>
                        <td>{{ $product->distribuidor->razon_social ?? 'Distribuidor' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:1rem; text-align:center;">
        @if ($product->esta_agotado)
            <h2 style="margin-top:0; font-size:1.1rem;">{{ $product->etiqueta_estado_stock }}</h2>
            @if ($product->estado_stock === 'pausado')
                <p style="color:#6b7280; margin:0 0 1rem 0;">
                    El distribuidor detuvo la publicación de este producto. Vuelve a contactarlo
                    para saber si volverá a estarlo.
                </p>
            @else
                <p style="color:#6b7280; margin:0 0 1rem 0;">
                    Este producto agotó sus existencias por el momento.
                    Puedes contactar al distribuidor para conocer la fecha de reposición.
                </p>
            @endif
            <a class="btn ghost" href="{{ route('suppliers.show', $product->distribuidor_id) }}">
                Ver catálogo del distribuidor
            </a>
        @elseif (Auth::check() && Auth::user()->rol === 'bodega')
            <h2 style="margin-top:0; font-size:1.1rem;">Solicitar cotización</h2>
            <p style="color:#6b7280; margin:0 0 1rem 0;">
                El pedido mínimo es de {{ $product->moq_cantidad_minima }} bultos.
                Te redirigimos al formulario para generar el resumen de WhatsApp.
            </p>
            <a class="btn" href="{{ route('quotes.create', ['producto' => $product->id]) }}">Cotizar pedido</a>
        @elseif (Auth::check())
            <h2 style="margin-top:0; font-size:1.1rem;">Acceso exclusivo para bodegas</h2>
            <p style="color:#6b7280; margin:0 0 1rem 0;">
                El flujo de cotización está habilitado para cuentas con rol bodega.
            </p>
            <a class="btn ghost" href="{{ route('products.index') }}">Volver al catálogo</a>
        @else
            {{-- Ficha CI-COD-13: el escenario exige modal informativo, no una tarjeta --}}
            <h2 style="margin-top:0; font-size:1.1rem;">Cotiza este producto en mayorista</h2>
            <p style="color:#6b7280; margin:0 0 1rem 0;">
                El pedido mínimo es de {{ $product->moq_cantidad_minima }} bultos.
                Inicia sesión o crea tu cuenta de bodega para generar el resumen de WhatsApp.
            </p>
            <button class="btn" type="button" data-modal-abrir="authModal">Cotizar pedido</button>
        @endif
    </div>

    @unless (Auth::check())
        <x-auth-modal :return-url="route('products.show', $product->id)" />
    @endunless
@endsection
