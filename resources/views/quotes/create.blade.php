@extends('layouts.app')

@section('title', 'Nueva cotización')

@section('content')
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-eyebrow">Cotización B2B</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Nueva cotización</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Elige el producto e indica cuántos bultos necesitas. Mayoro calcula el desglose con IGV
                y prepara el mensaje para enviarlo al distribuidor por WhatsApp.
            </p>
        </div>

        @auth
            <a class="btn ghost shrink-0" href="{{ route('quotes.index') }}">Ver mis cotizaciones</a>
        @else
            <a class="btn ghost shrink-0" href="{{ route('auth.register', ['returnUrl' => route('quotes.create')]) }}">
                Crear cuenta para guardar historial
            </a>
        @endauth
    </div>

    @guest
        <div class="surface-note mt-5" role="status">
            Estás cotizando como visitante: no pedimos RUC ni cuenta. Al generar la cotización verás el
            enlace de WhatsApp, pero no quedará guardada en un historial.
        </div>
    @endguest

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
        <section class="card">
            <form method="POST" action="{{ route('quotes.store') }}" id="quote-form" data-testid="cotizacion-form">
                @csrf

                <label for="producto_id">Producto del catálogo</label>
                <select id="producto_id" name="producto_id" required>
                    <option value="">Selecciona un producto...</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}"
                                data-nombre="{{ $producto->nombre }}"
                                data-moq="{{ $producto->moq_cantidad_minima }}"
                                data-precio-bulto="{{ $producto->precio_bulto }}"
                                data-unidades="{{ $producto->unidades_por_bulto }}"
                                data-stock="{{ $producto->stock_disponible }}"
                                data-tramos='@json($producto->tramosPrecio->map(fn ($tramo): array => ["cantidad_minima" => $tramo->cantidad_minima, "precio_bulto" => (float) $tramo->precio_bulto])->values())'
                                @selected(old('producto_id', $seleccionado?->id) == $producto->id)>
                            {{ $producto->nombre }} — {{ $producto->presentacion }}
                            (MOQ {{ $producto->moq_cantidad_minima }} bultos)
                        </option>
                    @endforeach
                </select>
                <span class="field-hint" id="moq-hint">
                    Selecciona un producto para ver su pedido mínimo, stock y precio por bulto.
                </span>

                <label for="cantidad_solicitada" class="mt-4">Cantidad de bultos</label>
                <input id="cantidad_solicitada" type="number" name="cantidad_solicitada" min="1" step="1"
                       value="{{ old('cantidad_solicitada', $seleccionado?->moq_cantidad_minima) }}" required>
                <span class="field-hint" id="stock-hint">El servidor valida el MOQ y el stock antes de registrar la cotización.</span>

                <div class="mt-6 flex flex-wrap gap-2">
                    <button class="btn" type="submit" data-testid="cotizacion-generar">Generar cotización</button>
                    <a class="btn ghost" href="{{ route('products.index') }}">Cancelar</a>
                </div>
            </form>
        </section>

        <aside class="card h-fit" data-testid="cotizacion-resumen">
            <p class="section-eyebrow">Vista previa</p>
            <h2 class="mt-1 text-lg font-semibold text-slate-900">Resumen de la cotización</h2>

            <p class="mt-3 text-sm text-slate-600" id="resumen-producto">
                Selecciona un producto para ver el resumen.
            </p>

            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-600">Precio por bulto</dt>
                    <dd class="m-0 font-semibold text-slate-900" id="resumen-precio">—</dd>
                </div>
                <p class="m-0 text-xs font-medium text-emerald-700" id="resumen-tramo" data-testid="cotizacion-tramo-preview" hidden></p>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-600">Bultos</dt>
                    <dd class="m-0 font-semibold text-slate-900" id="resumen-bultos">—</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-600">Subtotal</dt>
                    <dd class="m-0 font-semibold text-slate-900" id="resumen-subtotal">—</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-600">IGV (18%)</dt>
                    <dd class="m-0 font-semibold text-slate-900" id="resumen-igv">—</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-2">
                    <dt class="font-semibold text-slate-800">Total estimado</dt>
                    <dd class="m-0 text-lg font-semibold text-brand-800" id="resumen-total">—</dd>
                </div>
            </dl>

            <p class="surface-note mt-4">
                Mayoro genera el enlace de WhatsApp. El envío del mensaje lo realizas tú desde tu teléfono.
            </p>
        </aside>
    </div>

    <script>
        (function () {
            var select = document.getElementById('producto_id');
            var cantidad = document.getElementById('cantidad_solicitada');
            var hint = document.getElementById('moq-hint');
            var stockHint = document.getElementById('stock-hint');
            var resumenProducto = document.getElementById('resumen-producto');
            var resumenPrecio = document.getElementById('resumen-precio');
            var resumenTramo = document.getElementById('resumen-tramo');
            var resumenBultos = document.getElementById('resumen-bultos');
            var resumenSubtotal = document.getElementById('resumen-subtotal');
            var resumenIgv = document.getElementById('resumen-igv');
            var resumenTotal = document.getElementById('resumen-total');

            function soles(valor) {
                return 'S/ ' + valor.toLocaleString('es-PE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function sincronizar() {
                var opcion = select.options[select.selectedIndex];
                var moq = opcion ? Number(opcion.dataset.moq) : 0;
                var precio = opcion ? Number(opcion.dataset.precioBulto) : 0;
                var stock = opcion ? Number(opcion.dataset.stock) : 0;
                var unidades = opcion ? Number(opcion.dataset.unidades) : 0;

                if (! moq) {
                    hint.textContent = 'Selecciona un producto para ver su pedido mínimo, stock y precio por bulto.';
                    stockHint.textContent = 'El servidor valida el MOQ y el stock antes de registrar la cotización.';
                    resumenProducto.textContent = 'Selecciona un producto para ver el resumen.';
                    resumenPrecio.textContent = '—';
                    resumenTramo.hidden = true;
                    resumenBultos.textContent = '—';
                    resumenSubtotal.textContent = '—';
                    resumenIgv.textContent = '—';
                    resumenTotal.textContent = '—';
                    return;
                }

                hint.textContent = 'MOQ: ' + moq + ' bultos · stock: ' + stock + ' bultos · ' + unidades + ' unidades por bulto.';
                stockHint.textContent = 'Puedes pedir entre ' + moq + ' y ' + stock + ' bultos.';
                cantidad.min = moq;
                cantidad.max = stock;

                if (parseInt(cantidad.value || '0', 10) < moq) {
                    cantidad.value = moq;
                }

                if (parseInt(cantidad.value || '0', 10) > stock) {
                    cantidad.value = stock;
                }

                var bultos = parseInt(cantidad.value || '0', 10);
                var tramos = [];

                try {
                    tramos = JSON.parse(opcion.dataset.tramos || '[]');
                } catch (error) {
                    tramos = [];
                }

                // RN-04: un solo tramo, el de mayor umbral que no supere la
                // cantidad. El servidor repite esta misma seleccion al guardar.
                var tramoAplicado = null;
                var siguienteTramo = null;

                tramos.forEach(function (tramo) {
                    var umbral = Number(tramo.cantidad_minima);

                    if (umbral <= bultos && (tramoAplicado === null || umbral > Number(tramoAplicado.cantidad_minima))) {
                        tramoAplicado = tramo;
                    }

                    if (umbral > bultos && (siguienteTramo === null || umbral < Number(siguienteTramo.cantidad_minima))) {
                        siguienteTramo = tramo;
                    }
                });

                var precioAplicable = tramoAplicado ? Number(tramoAplicado.precio_bulto) : precio;
                var subtotal = precioAplicable * bultos;
                var igv = subtotal * 0.18;

                if (tramoAplicado) {
                    resumenTramo.textContent = 'Precio por volumen aplicado desde ' + tramoAplicado.cantidad_minima + ' bultos.';
                    resumenTramo.hidden = false;
                } else if (siguienteTramo) {
                    resumenTramo.textContent = 'Desde ' + siguienteTramo.cantidad_minima + ' bultos el precio baja a ' + soles(Number(siguienteTramo.precio_bulto)) + ' por bulto.';
                    resumenTramo.hidden = false;
                } else {
                    resumenTramo.hidden = true;
                }

                resumenProducto.textContent = opcion.dataset.nombre || '';
                resumenPrecio.textContent = soles(precioAplicable);
                resumenBultos.textContent = bultos;
                resumenSubtotal.textContent = soles(subtotal);
                resumenIgv.textContent = soles(igv);
                resumenTotal.textContent = soles(subtotal + igv);
            }

            select.addEventListener('change', sincronizar);
            cantidad.addEventListener('input', sincronizar);
            sincronizar();
        })();
    </script>
@endsection
