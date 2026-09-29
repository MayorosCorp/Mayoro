@extends('layouts.app')

@section('title', 'Editar pedido')

@section('content')
    <h1>Editar pedido</h1>

    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('orders.update', $order) }}">
            @csrf
            @method('PUT')

            <label for="supplier">Proveedor</label>
            <select id="supplier" name="supplier_id">
                <option value="">Selecciona un proveedor</option>
            </select>

            <label for="expected_date">Fecha estimada de entrega</label>
            <input id="expected_date" type="date" name="expected_date" value="{{ old('expected_date') }}">

            <div style="margin-top:1.2rem;">
                <button class="btn" type="submit">Actualizar pedido</button>
                <a class="btn ghost" href="{{ route('orders.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection