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

    @if (auth()->user()->esEmpresario())
        <div class="card mt-6">
            <h2 class="mt-0 text-lg font-semibold text-slate-900">Gestionar mi catálogo mayorista</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Publica un producto con sus condiciones de bulto, MOQ, stock e imagen, o administra
                los que ya están publicados. El catálogo es público: cualquier persona puede verlo.
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a class="btn accent" href="{{ route('products.create') }}">+ Publicar Nuevo Producto</a>
                <a class="btn ghost" href="{{ route('products.index') }}">Ver catálogo público</a>
            </div>
        </div>
    @else
        {{--
            La publicación es exclusiva del Empresario, pero la Persona Natural
            no se queda con un callejón sin salida: el sistema le explica cómo
            habilitar ese rol con la validación de RUC (RN-02).
        --}}
        <div class="card mt-6 border-brand-200 bg-brand-50">
            <h2 class="mt-0 text-lg font-semibold text-brand-900">¿Quieres ofrecer tus productos en Mayoro?</h2>
            <p class="mt-2 text-sm leading-6 text-brand-900">
                Tu cuenta está registrada como <strong>Persona natural</strong>, el perfil con el que
                puedes explorar el catálogo, comparar precios por bulto y cotizar con cualquier
                distribuidor por WhatsApp, sin necesidad de RUC.
            </p>
            <p class="mt-2 text-sm leading-6 text-brand-900">
                Para publicar productos y recibir cotizaciones necesitas convertirte en
                <strong>Empresario</strong>. Es un trámite único: validamos tu RUC con el dígito
                verificador del Módulo 11 y los datos de tu negocio.
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a class="btn accent" href="{{ route('profile.empresario') }}">Convertirme en Empresario</a>
                <a class="btn ghost" href="{{ route('products.index') }}">Explorar el catálogo</a>
                <a class="btn ghost" href="{{ route('quotes.index') }}">Ver mis cotizaciones</a>
            </div>
        </div>
    @endif

    <div class="card mt-6">
        <h2 class="mt-0 text-lg font-semibold text-slate-900">Actividad reciente</h2>
        <p class="mb-0 text-sm text-slate-600">No hay actividad reciente para mostrar.</p>
    </div>
@endsection
