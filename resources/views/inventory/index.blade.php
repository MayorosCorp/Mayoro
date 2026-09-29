@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
    <div style="display:flex; align-items:center; justify-content:space-between;">
        <h1>Inventario</h1>
        <a class="btn" href="{{ route('inventory.create') }}">Registrar entrada</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Producto</th>
                    <th>Stock actual</th>
                    <th>Mínimo</th>
                    <th>Almacén</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" style="text-align:center; color:#6b7280;">No hay movimientos de inventario.</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection