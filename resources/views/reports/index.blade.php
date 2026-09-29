@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
    <h1>Reportes</h1>

    <div class="card" style="max-width:640px;">
        <form method="POST" action="{{ route('reports.generate') }}">
            @csrf

            <label for="type">Tipo de reporte</label>
            <select id="type" name="type">
                <option value="quotes">Cotizaciones</option>
                <option value="orders">Pedidos</option>
                <option value="inventory">Inventario</option>
                <option value="suppliers">Proveedores</option>
            </select>

            <label for="from">Desde</label>
            <input id="from" type="date" name="from">

            <label for="to">Hasta</label>
            <input id="to" type="date" name="to">

            <div style="margin-top:1.2rem;">
                <button class="btn" type="submit">Generar reporte</button>
            </div>
        </form>
    </div>
@endsection