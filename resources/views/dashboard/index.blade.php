@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1>Dashboard</h1>

    <div class="grid">
        <div class="stat">
            <span class="value">{{ $metricas['productos'] }}</span>
            <span class="label">{{ $metricas['productosEtiqueta'] }}</span>
        </div>
        <div class="stat">
            <span class="value">{{ $metricas['cotizaciones'] }}</span>
            <span class="label">Cotizaciones pendientes</span>
        </div>
        <div class="stat">
            <span class="value">{{ $pedidosDisponibles ? '0' : '—' }}</span>
            <span class="label">Pedidos en curso</span>
        </div>
        <div class="stat">
            <span class="value">{{ $metricas['proveedores'] }}</span>
            <span class="label">Distribuidores registrados</span>
        </div>
    </div>

    @if (auth()->user()->rol === 'distribuidor')
        <div class="card" style="margin-top:1.5rem;">
            <h2>Gestionar mi catálogo mayorista</h2>
            <p style="color:#6b7280; font-size:.9rem; margin:.25rem 0 1rem;">
                Publica un producto con sus condiciones de bulto, MOQ, stock e imagen, o administra
                los que ya están publicados. El catálogo es público: cualquier bodega puede verlo.
            </p>
            <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
                <a class="btn" href="{{ route('products.create') }}">+ Publicar Nuevo Producto</a>
                <a class="btn ghost" href="{{ route('products.index') }}">Ver catálogo público</a>
            </div>
        </div>
    @endif

    <div class="card" style="margin-top:1.5rem;">
        <h2>Actividad reciente</h2>
        <p>No hay actividad reciente para mostrar.</p>
    </div>
@endsection
