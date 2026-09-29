@extends('layouts.app')

@section('title', 'Cotizaciones')

@section('content')
    <div style="display:flex; align-items:center; justify-content:space-between;">
        <h1>Cotizaciones</h1>
        @if (Auth::user()->rol === 'bodega')
            <a class="btn" href="{{ route('quotes.create') }}">Nueva cotización</a>
        @endif
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Contraparte</th>
                    <th>Bultos</th>
                    <th>Total (IGV incl.)</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($quotes as $quote)
                    <tr>
                        <td>#{{ $quote->id }}</td>
                        <td>{{ $quote->producto->nombre ?? 'Producto eliminado' }}</td>
                        <td>
                            {{ Auth::user()->rol === 'distribuidor'
                                ? ($quote->bodega->razon_social ?? 'Bodega')
                                : ($quote->distribuidor->razon_social ?? 'Distribuidor') }}
                        </td>
                        <td>{{ $quote->cantidad_solicitada }}</td>
                        <td><strong>S/ {{ number_format((float) $quote->total, 2) }}</strong></td>
                        <td>
                            <span style="background:{{ $quote->estado === 'enviada' ? '#dbeafe' : '#fef3c7' }};
                                         color:{{ $quote->estado === 'enviada' ? '#1d4ed8' : '#92400e' }};
                                         padding:.2rem .5rem; border-radius:4px; font-size:.85rem; font-weight:600;">
                                {{ ucfirst($quote->estado) }}
                            </span>
                        </td>
                        <td>{{ $quote->created_at->format('d/m/Y H:i') }}</td>
                        <td><a href="{{ route('quotes.show', $quote) }}">Ver</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; color:#6b7280;">
                            No hay cotizaciones registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($quotes->hasPages())
            <div style="margin-top:1rem;">{{ $quotes->links() }}</div>
        @endif
    </div>
@endsection
