@extends('layouts.app')

@section('title', 'Nueva cotización')

@section('content')
    <h1>Nueva cotización</h1>
    <p style="color:#6b7280; margin:.25rem 0 1.5rem 0; font-size:.95rem;">
        Elige el producto, indica los bultos y Mayoro arma el resumen comercial para enviarlo por WhatsApp.
    </p>

    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('quotes.store') }}" id="quote-form">
            @csrf

            <label for="producto_id">Producto del catálogo</label>
            <select id="producto_id" name="producto_id" required>
                <option value="">Selecciona un producto...</option>
                @foreach ($productos as $producto)
                    <option value="{{ $producto->id }}"
                            data-moq="{{ $producto->moq_cantidad_minima }}"
                            data-precio="{{ $producto->precio_unitario_calculado }}"
                            data-unidades="{{ $producto->unidades_por_bulto }}"
                            @selected(old('producto_id', $seleccionado?->id) == $producto->id)>
                        {{ $producto->nombre }} — {{ $producto->presentacion }}
                        (MOQ {{ $producto->moq_cantidad_minima }} bultos)
                    </option>
                @endforeach
            </select>

            <label for="cantidad_solicitada">Cantidad de bultos</label>
            <input id="cantidad_solicitada" type="number" name="cantidad_solicitada" min="1"
                   value="{{ old('cantidad_solicitada', $seleccionado?->moq_cantidad_minima) }}" required>
            <small id="moq-hint" style="display:block; color:#6b7280; font-size:.8rem; margin-top:.3rem;">
                Selecciona un producto para ver su pedido mínimo.
            </small>

            <div style="margin-top:1.2rem;">
                <button class="btn" type="submit">Generar cotización</button>
                <a class="btn ghost" href="{{ route('quotes.index') }}">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            var select = document.getElementById('producto_id');
            var cantidad = document.getElementById('cantidad_solicitada');
            var hint = document.getElementById('moq-hint');

            function sincronizar() {
                var opcion = select.options[select.selectedIndex];
                var moq = opcion ? opcion.dataset.moq : null;

                if (!moq) {
                    hint.textContent = 'Selecciona un producto para ver su pedido mínimo.';
                    return;
                }

                hint.textContent = 'Pedido mínimo exigido por el distribuidor: ' + moq + ' bultos.';
                cantidad.min = moq;

                if (parseInt(cantidad.value || '0', 10) < parseInt(moq, 10)) {
                    cantidad.value = moq;
                }
            }

            select.addEventListener('change', sincronizar);
            sincronizar();
        })();
    </script>
@endsection
