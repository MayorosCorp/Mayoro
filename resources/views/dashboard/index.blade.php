@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div>
        <p class="section-eyebrow">Panel de trabajo</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Dashboard</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
            Un vistazo al catálogo y al movimiento comercial de tu cuenta.
        </p>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat">
            <span class="value">{{ $metricas['productos'] }}</span>
            <span class="label">{{ $metricas['productosEtiqueta'] }}</span>
        </div>
        <div class="stat">
            <span class="value">{{ $metricas['cotizaciones'] }}</span>
            <span class="label">{{ $metricas['cotizacionesEtiqueta'] }}</span>
        </div>
        <div class="stat">
            <span class="value">{{ $metricas['noCotizables'] }}</span>
            <span class="label">Productos no cotizables</span>
        </div>
        <div class="stat">
            <span class="value">{{ $metricas['proveedores'] }}</span>
            <span class="label">Distribuidores registrados</span>
        </div>
    </div>

    @if (auth()->user()->rol === 'distribuidor')
        <div class="card mt-6">
            <h2 class="mt-0 text-lg font-semibold text-slate-900">Gestionar mi catálogo mayorista</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Publica un producto con sus condiciones de bulto, MOQ, stock e imagen, o administra
                los que ya están publicados. El catálogo es público: cualquier bodega puede verlo.
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a class="btn accent" href="{{ route('products.create') }}">+ Publicar Nuevo Producto</a>
                <a class="btn ghost" href="{{ route('products.index') }}">Ver catálogo público</a>
            </div>
        </div>
    @else
        {{--
            El acceso a publicar se oculta a la bodega sin explicar por que, lo que
            deja al usuario buscando un boton que el sistema nunca le ofrecio. Se
            dice explicitamente que la publicacion es exclusiva del mayorista.
        --}}
        <div class="card mt-6 border-brand-200 bg-brand-50">
            <h2 class="mt-0 text-lg font-semibold text-brand-900">¿Quieres ofrecer tus productos en Mayoro?</h2>
            <p class="mt-2 text-sm leading-6 text-brand-900">
                Tu cuenta está registrada como <strong>Bodega</strong>, que es el perfil de quien
                compra al por mayor. La publicación de productos es exclusiva del perfil
                <strong>Distribuidor Mayorista</strong>, por eso aquí no aparece la opción de crear
                un producto.
            </p>
            <p class="mt-2 text-sm leading-6 text-brand-900">
                Tu rol actual es <strong>{{ auth()->user()->rol }}</strong>. Puedes explorar el
                catálogo, comparar precios referenciales y cotizar directamente con cualquier
                distribuidor por WhatsApp.
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a class="btn" href="{{ route('products.index') }}">Explorar el catálogo</a>
                <a class="btn ghost" href="{{ route('quotes.index') }}">Ver mis cotizaciones</a>
            </div>
        </div>
    @endif

    <div class="card mt-6">
        <h2 class="mt-0 text-lg font-semibold text-slate-900">Actividad reciente</h2>
        <p class="mb-0 text-sm text-slate-600">No hay actividad reciente para mostrar.</p>
    </div>
@endsection
