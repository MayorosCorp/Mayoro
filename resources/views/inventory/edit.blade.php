@extends('layouts.app')

@section('title', 'Editar movimiento de inventario')

@section('content')
    <h1>Editar movimiento de inventario</h1>

    <div class="card" style="max-width:640px;">
        <form method="POST" action="{{ route('inventory.update', $inventory) }}">
            @csrf
            @method('PUT')

            <label for="quantity">Cantidad</label>
            <input id="quantity" type="number" min="1" name="quantity" value="{{ old('quantity') }}" required>

            <label for="type">Tipo de movimiento</label>
            <select id="type" name="type">
                <option value="in">Entrada</option>
                <option value="out">Salida</option>
                <option value="adjust">Ajuste</option>
            </select>

            <div style="margin-top:1.2rem;">
                <button class="btn" type="submit">Actualizar movimiento</button>
                <a class="btn ghost" href="{{ route('inventory.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
@endsection