@extends('layouts.app')

@section('title', 'Detalle del pedido')

@section('content')
    <h1>Pedido #{{ $order }}</h1>

    <div class="card">
        <p><strong>Cotización:</strong> —</p>
        <p><strong>Proveedor:</strong> —</p>
        <p><strong>Estado:</strong> —</p>
        <p><strong>Fecha estimada:</strong> —</p>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" style="text-align:center; color:#6b7280;">Sin líneas de pedido.</td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top:1.2rem;">
            <a class="btn" href="#">Marcar como recibido</a>
            <a class="btn ghost" href="{{ route('orders.index') }}">Volver</a>
        </div>
    </div>
@endsection