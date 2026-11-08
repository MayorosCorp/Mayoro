@extends('layouts.app')

@section('title', 'Distribuidores')

@section('content')
    <section>
        <p class="section-eyebrow">Directorio B2B</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
            Distribuidores mayoristas
        </h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
            Comercios verificados que publican productos con precio por bulto y pedido mínimo
            en el catálogo de Mayoro.
        </p>
    </section>

    <form method="GET" action="{{ route('suppliers.index') }}" class="card mt-6">
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-[2fr_auto]">
            <div>
                <label for="q">Buscar distribuidor</label>
                <input id="q" type="search" name="q" value="{{ $busqueda }}"
                       placeholder="Razón social o RUC...">
            </div>
            <div class="flex gap-2">
                <button class="btn" type="submit">Buscar</button>
                @if ($busqueda !== '')
                    <a class="btn ghost" href="{{ route('suppliers.index') }}">Limpiar</a>
                @endif
            </div>
        </div>
    </form>

    <div class="mt-5 flex flex-wrap items-center gap-2 text-sm text-slate-600">
        <span class="font-semibold text-slate-800">{{ $distribuidores->total() }}</span>
        <span>distribuidor(es) registrado(s)</span>

        @if ($busqueda !== '')
            <span class="badge badge-brand">búsqueda: {{ $busqueda }}</span>
        @endif
    </div>

    <div class="card-grid mt-4">
        @forelse ($distribuidores as $distribuidor)
            <article class="card card-hover flex flex-col gap-3" data-testid="distribuidor-card">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge badge-muted">RUC {{ $distribuidor->ruc_empresa }}</span>

                    @if ($distribuidor->is_premium)
                        <span class="badge badge-accent">Distribuidor Premium</span>
                    @endif
                </div>

                <a href="{{ route('suppliers.show', $distribuidor->id) }}"
                   class="break-words text-lg font-semibold leading-snug text-slate-900 no-underline hover:text-brand-700">
                    {{ $distribuidor->razon_social }}
                </a>

                <p class="m-0 text-sm text-slate-600">
                    <span class="font-semibold text-slate-800">{{ $distribuidor->productos_count }}</span>
                    producto(s) activo(s) en catálogo
                </p>

                <a class="btn ghost mt-auto w-full" href="{{ route('suppliers.show', $distribuidor->id) }}">
                    Ver catálogo
                </a>
            </article>
        @empty
            <div class="card col-span-full">
                <h2 class="mt-0 text-lg font-semibold text-slate-900">No hay distribuidores que coincidan</h2>
                <p class="mb-0 text-sm leading-6 text-slate-600">
                    @if ($busqueda !== '')
                        Ningún distribuidor coincide con «{{ $busqueda }}».
                        <a href="{{ route('suppliers.index') }}">Ver todos</a>.
                    @else
                        Todavía no hay comercios distribuidores registrados.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    @if ($distribuidores->hasPages())
        <div class="mt-8">{{ $distribuidores->links() }}</div>
    @endif
@endsection
