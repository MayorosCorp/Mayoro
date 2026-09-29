@extends('layouts.app')

@section('title', 'Catálogo Mayorista de Productos - Mayoro B2B')

@section('content')
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
        <div>
            <h1>Catálogo de Productos Mayoristas</h1>
            <p style="color:#666; margin:0.25rem 0 0 0; font-size:0.95rem;">
                Explora las condiciones mayoristas, empaque y pedido mínimo (MOQ) de los distribuidores oficiales.
            </p>
        </div>

        @if (Auth::check() && Auth::user()->rol === 'distribuidor')
            <a class="btn" href="{{ route('products.create') }}">+ Publicar Nuevo Producto</a>
        @endif
    </div>

    @if (session('success'))
        <div style="background:#dcfce7; border:1px solid #22c55e; color:#15803d; padding:0.75rem 1rem; border-radius:6px; margin-bottom:1.5rem;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card" style="padding:0; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; text-align:left;">
                    <th style="padding:0.75rem 1rem;">Producto</th>
                    <th style="padding:0.75rem 1rem;">Categoría</th>
                    <th style="padding:0.75rem 1rem;">Presentación</th>
                    <th style="padding:0.75rem 1rem;">Precio Bulto</th>
                    <th style="padding:0.75rem 1rem;">Precio Unit. Sugerido</th>
                    <th style="padding:0.75rem 1rem;">Pedido Mínimo (MOQ)</th>
                    <th style="padding:0.75rem 1rem;">Distribuidor</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $prod)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.75rem 1rem; font-weight:600;">{{ $prod->nombre }}</td>
                        <td style="padding:0.75rem 1rem;">
                            <span style="background:#e0f2fe; color:#0369a1; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.85rem;">
                                {{ $prod->categoria }}
                            </span>
                        </td>
                        <td style="padding:0.75rem 1rem;">{{ $prod->presentacion }} ({{ $prod->unidades_por_bulto }} unids)</td>
                        <td style="padding:0.75rem 1rem; font-weight:700; color:#0f766e;">S/ {{ number_format($prod->precio_bulto, 2) }}</td>
                        <td style="padding:0.75rem 1rem; color:#64748b;">S/ {{ number_format($prod->precio_unitario_sugerido, 2) }}</td>
                        <td style="padding:0.75rem 1rem;">
                            <span style="background:#fef3c7; color:#92400e; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.85rem; font-weight:600;">
                                {{ $prod->moq_cantidad_minima }} bultos
                            </span>
                        </td>
                        <td style="padding:0.75rem 1rem;">{{ $prod->distribuidor->razon_social ?? 'Distribuidor' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:2rem; color:#6b7280;">
                            No hay productos registrados en el catálogo mayorista actualmente.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection