@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
    <section class="mx-auto max-w-2xl">
        <p class="section-eyebrow">Módulo en preparación</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Inventario</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            El control de almacén todavía no está disponible. Por ahora el stock se gestiona
            desde la edición de cada producto publicado.
        </p>

        <div class="card mt-6">
            <h2 class="mt-0 text-lg font-semibold text-slate-900">Stock y catálogo</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Un producto se puede cotizar cuando está publicado y su stock alcanza el pedido
                mínimo (MOQ). Si el stock es 0, el catálogo lo muestra como
                <em>«Temporalmente sin stock»</em>.
            </p>

            <div class="mt-5 flex flex-wrap gap-3">
                <a class="btn" href="{{ route('products.index') }}">Ir al catálogo</a>
                <a class="btn ghost" href="{{ route('dashboard.index') }}">Volver al dashboard</a>
            </div>
        </div>
    </section>
@endsection
