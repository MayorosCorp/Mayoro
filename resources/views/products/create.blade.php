@extends('layouts.app')

@section('title', 'Publicar Producto Mayorista - Mayoro B2B')

@section('content')
    <div class="mx-auto max-w-3xl">
        <nav class="mb-4 text-sm" aria-label="Migas de pan">
            <a href="{{ route('products.index') }}" class="font-medium text-brand-700">&larr; Volver al catálogo</a>
        </nav>

        <header>
            <p class="section-eyebrow">Catálogo del distribuidor</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">
                Publicar producto mayorista
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                El precio se publica por bulto. La bodega verá también el precio unitario
                referencial, que se calcula dividiendo el precio del bulto entre las unidades
                del empaque.
            </p>
        </header>

        @if (session('success'))
            <div class="flash success mt-6" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="flash error mt-6" role="alert">
                <div>
                    <p class="m-0 font-semibold">Por favor corrige los siguientes errores:</p>
                    <ul class="error-list mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data"
              class="card mt-6" data-testid="producto-form">
            @csrf

            <fieldset class="m-0 border-0 p-0">
                <legend class="text-base font-semibold text-slate-900">Identidad del producto</legend>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-[2fr_1fr]">
                    <div>
                        <label for="nombre">Nombre Comercial del Producto</label>
                        <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}"
                               placeholder="Ej. Aceite Primor 1L x 12" required>
                    </div>
                    <div>
                        <label for="categoria">Categoría</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Selecciona...</option>
                            <option value="Abarrotes" @selected(old('categoria') === 'Abarrotes')>Abarrotes</option>
                            <option value="Bebidas" @selected(old('categoria') === 'Bebidas')>Bebidas</option>
                            <option value="Lácteos" @selected(old('categoria') === 'Lácteos')>Lácteos</option>
                            <option value="Limpieza" @selected(old('categoria') === 'Limpieza')>Limpieza</option>
                            <option value="Golosinas" @selected(old('categoria') === 'Golosinas')>Golosinas</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label for="descripcion">Descripción del Producto y Condiciones Comerciales (Opcional)</label>
                    <textarea id="descripcion" name="descripcion" rows="3"
                              placeholder="Detalles de conservación, fecha de vencimiento o promociones por volumen...">{{ old('descripcion') }}</textarea>
                </div>
            </fieldset>

            <hr class="my-6 border-slate-200">

            <fieldset class="m-0 border-0 p-0">
                <legend class="text-base font-semibold text-slate-900">Empaque y precio</legend>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="presentacion">Presentación de Empaque</label>
                        <input id="presentacion" type="text" name="presentacion" value="{{ old('presentacion') }}"
                               placeholder="Ej. Caja, Fardo, Saco, Display" required>
                    </div>
                    <div>
                        <label for="unidades_por_bulto">Unidades por Empaque</label>
                        <input id="unidades_por_bulto" type="number" min="1" step="1" name="unidades_por_bulto"
                               value="{{ old('unidades_por_bulto', 1) }}" required>
                    </div>
                    <div>
                        <label for="precio_bulto">Precio por Bulto / Empaque (S/.)</label>
                        <input id="precio_bulto" type="number" step="0.01" min="0.01" name="precio_bulto"
                               value="{{ old('precio_bulto') }}" placeholder="0.00" required>
                        <span class="field-hint">Máximo dos decimales. No incluye IGV.</span>
                    </div>
                    <div>
                        <label for="moq_cantidad_minima">Pedido Mínimo Mayorista (MOQ)</label>
                        <input id="moq_cantidad_minima" type="number" min="1" step="1" name="moq_cantidad_minima"
                               value="{{ old('moq_cantidad_minima', 1) }}" placeholder="Mínimo 1" required
                               data-moq-input>
                        <span class="field-hint">Bultos mínimos por cotización.</span>
                    </div>
                </div>
            </fieldset>

            <hr class="my-6 border-slate-200">

            <fieldset class="m-0 border-0 p-0">
                <legend class="text-base font-semibold text-slate-900">Disponibilidad</legend>

                <div class="mt-4">
                    <label for="stock_disponible">Unidades Disponibles en Stock</label>
                    <input id="stock_disponible" type="number" min="0" step="1" name="stock_disponible"
                           value="{{ old('stock_disponible', 0) }}" required>
                    <span class="field-hint" data-stock-hint>
                        Debe ser <strong>0</strong> o al menos el pedido mínimo
                        (<strong data-moq-value>{{ old('moq_cantidad_minima', 1) }}</strong> bultos). Si dejas 0, el producto se
                        publica igualmente pero el catálogo lo mostrará como
                        <em>«Temporalmente sin stock»</em>, sin opción de cotizar.
                    </span>
                </div>

                <div class="surface-note mt-4">
                    <strong>Cálculo automático B2B:</strong> el precio unitario sugerido para la
                    bodega se calcula dividiendo el precio por bulto entre las unidades del empaque.
                    No se ingresa a mano.
                </div>
            </fieldset>

            <hr class="my-6 border-slate-200">

            <fieldset class="m-0 border-0 p-0">
                <legend class="text-base font-semibold text-slate-900">Imagen</legend>

                <div class="mt-4">
                    <label for="imagen">Imagen del Producto (Opcional)</label>
                    <input id="imagen" type="file" name="imagen" accept="image/png,image/jpeg,image/webp">
                    <span class="field-hint">
                        Formatos admitidos: PNG, JPG o WebP. Peso máximo 2 MB.
                        El archivo se almacena con nombre saneado y ruta generada por el servidor.
                    </span>
                </div>
            </fieldset>

            <div class="mt-8 flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                <button class="btn sm:flex-1" type="submit" data-testid="producto-publicar">
                    Publicar en Catálogo Mayorista
                </button>
                <a class="btn ghost" href="{{ route('products.index') }}">Cancelar</a>
            </div>
        </form>
    </div>

    {{-- La regla de stock depende del MOQ que se escriba arriba. Se refleja en
         el texto de ayuda para que el distribuidor no descubra el límite con un
         error de validación al enviar. --}}
    <script>
        (function () {
            var moqInput = document.querySelector('[data-moq-input]');
            var moqValue = document.querySelector('[data-moq-value]');
            if (! moqInput || ! moqValue) { return; }

            moqInput.addEventListener('input', function () {
                var valor = parseInt(moqInput.value, 10);
                moqValue.textContent = (! isNaN(valor) && valor > 0) ? valor : 1;
            });
        })();
    </script>
@endsection
