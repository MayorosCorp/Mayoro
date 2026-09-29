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
    @else
        {{--
            El acceso a publicar se oculta a la bodega sin explicar por que, lo que
            deja al usuario buscando un boton que el sistema nunca le ofrecio. Se
            dice explicitamente que la publicacion es exclusiva del mayorista.
        --}}
        <div class="card" style="margin-top:1.5rem; border-color:#bfdbfe; background:#eff6ff;">
            <h2>¿Quieres ofrecer tus productos en Mayoro?</h2>
            <p style="color:#1e40af; font-size:.9rem; margin:.25rem 0 1rem;">
                Tu cuenta está registrada como <strong>Bodega</strong>, que es el perfil de quien
                compra al por mayor. La publicación de productos es exclusiva del perfil
                <strong>Distribuidor Mayorista</strong>, por eso aquí no aparece la opción de crear
                un producto.
            </p>
            <p style="color:#1e40af; font-size:.9rem; margin:0 0 1rem 0;">
                Tu rol actual es <strong>{{ auth()->user()->rol }}</strong>. Puedes explorar el
                catálogo, comparar precios referenciales y cotizar directamente con cualquier
                distribuidor por WhatsApp.
            </p>
            <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
                <a class="btn" href="{{ route('products.index') }}">Explorar el catálogo</a>
                <a class="btn ghost" href="{{ route('quotes.index') }}">Ver mis cotizaciones</a>
            </div>
        </div>
    @endif

    <div class="card" style="margin-top:1.5rem;">
        <h2>Actividad reciente</h2>
        <p>No hay actividad reciente para mostrar.</p>
    </div>
@endsection
