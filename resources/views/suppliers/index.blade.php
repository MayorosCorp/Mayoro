@extends('layouts.app')

@section('title', 'Distribuidores')

@section('content')
    <h1 style="margin-bottom:.25rem;">Distribuidores mayoristas</h1>
    <p style="color:#6b7280; margin:0 0 1.5rem 0; font-size:.95rem;">
        Comercios verificados que publican productos con precio por bulto y pedido mínimo
        en el catálogo de Mayoro.
    </p>

    <form method="GET" action="{{ route('suppliers.index') }}" class="card">
        <div style="display:grid; grid-template-columns:2fr auto; gap:1rem; align-items:end;">
            <div>
                <label for="q">Buscar distribuidor</label>
                <input id="q" type="search" name="q" value="{{ $busqueda }}"
                       placeholder="Razón social o RUC...">
            </div>
            <div style="display:flex; gap:.5rem;">
                <button class="btn" type="submit">Buscar</button>
                @if ($busqueda !== '')
                    <a class="btn ghost" href="{{ route('suppliers.index') }}">Limpiar</a>
                @endif
            </div>
        </div>
    </form>

    <div class="grid" style="margin-top:1.5rem;">
        @forelse ($distribuidores as $distribuidor)
            <article class="card" style="display:flex; flex-direction:column; gap:.4rem;">
                <span style="font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:#6b7280;">
                    RUC {{ $distribuidor->ruc_empresa }}
                </span>

                <a href="{{ route('suppliers.show', $distribuidor->id) }}"
                   style="font-weight:700; font-size:1.05rem; text-decoration:none; color:#1f2937;">
                    {{ $distribuidor->razon_social }}
                </a>

                <span style="font-size:.82rem; color:#6b7280;">
                    {{ $distribuidor->productos_count }}
                    producto(s) activo(s) en catálogo
                </span>

                @if ($distribuidor->is_premium)
                    <span>
                        <span style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;
                                     border-radius:999px; padding:.1rem .55rem; font-size:.72rem;">
                            Distribuidor Premium
                        </span>
                    </span>
                @endif

                <a class="btn" style="margin-top:.5rem;" href="{{ route('suppliers.show', $distribuidor->id) }}">
                    Ver catálogo
                </a>
            </article>
        @empty
            <div class="card">
                <h3 style="margin-top:0;">No hay distribuidores que coincidan</h3>
                <p style="color:#6b7280; margin:0;">
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
        <div style="margin-top:2rem;">{{ $distribuidores->links() }}</div>
    @endif
@endsection
