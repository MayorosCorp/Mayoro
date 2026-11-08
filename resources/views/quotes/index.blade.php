@extends('layouts.app')

@section('title', 'Cotizaciones')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-eyebrow">Seguimiento comercial</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Cotizaciones</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Historial de cotizaciones generadas. El estado <strong>generada</strong> significa que
                Mayoro preparó el enlace de WhatsApp; la conversación y el cierre ocurren fuera de la plataforma.
            </p>
        </div>

        @if (Auth::user()->esPersonaNatural())
            <a class="btn shrink-0" href="{{ route('quotes.create') }}" data-testid="cotizaciones-nueva">
                Nueva cotización
            </a>
        @endif
    </div>

    <div class="card mt-6 overflow-x-auto p-0">
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
                    <th scope="col" aria-label="Acciones"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($quotes as $quote)
                    @php
                        $estadoClases = match ($quote->estado) {
                            'generada' => 'badge-brand',
                            'aceptada' => 'badge-success',
                            'rechazada' => 'badge-danger',
                            default => 'badge-muted',
                        };
                    @endphp
                    <tr>
                        <td class="font-semibold text-slate-900">#{{ $quote->id }}</td>
                        <td>{{ $quote->producto->nombre ?? 'Producto eliminado' }}</td>
                        <td>
                            {{ Auth::user()->esEmpresario()
                                ? ($quote->bodega->razon_social ?? '-')
                                : ($quote->distribuidor->razon_social ?? '-') }}
                        </td>
                        <td>{{ $quote->cantidad_solicitada }}</td>
                        <td class="font-semibold text-slate-900">S/ {{ number_format((float) $quote->total, 2) }}</td>
                        <td><span class="badge {{ $estadoClases }}">{{ ucfirst($quote->estado) }}</span></td>
                        <td class="whitespace-nowrap text-slate-500">{{ $quote->created_at?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            <a class="btn ghost sm" href="{{ route('quotes.show', $quote) }}">Ver detalle</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="py-8 text-center">
                                <h2 class="mt-0 text-lg font-semibold text-slate-900">Todavía no hay cotizaciones</h2>
                                <p class="mb-4 mt-1 text-sm text-slate-600">
                                    @if (Auth::user()->esEmpresario())
                                        Cuando un comprador genere una cotización de tus productos, aparecerá aquí
                                        con su desglose y el enlace de WhatsApp.
                                    @else
                                        Cuando generes una cotización, aparecerá aquí con su desglose y su enlace de
                                        WhatsApp.
                                    @endif
                                </p>
                                @if (Auth::user()->esPersonaNatural())
                                    <a class="btn" href="{{ route('quotes.create') }}">Nueva cotización</a>
                                @else
                                    <a class="btn" href="{{ route('products.index') }}">Ver el catálogo</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($quotes->hasPages())
        <div class="mt-6">{{ $quotes->links() }}</div>
    @endif
@endsection
