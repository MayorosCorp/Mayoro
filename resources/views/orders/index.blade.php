@extends('layouts.app')

@section('title', 'Pedidos')

@section('content')
    <div style="display:flex; align-items:center; justify-content:space-between;">
        <h1>Pedidos</h1>
        <a class="btn" href="{{ route('orders.create') }}">Nuevo pedido</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cotización</th>
                    <th>Proveedor</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" style="text-align:center; color:#6b7280;">No hay pedidos registrados.</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection