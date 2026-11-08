@extends('layouts.app')

@section('title', 'Editar producto')

@section('content')
    <div class="mx-auto max-w-3xl">
        <nav class="mb-4 text-sm" aria-label="Migas de pan">
            <a href="{{ route('products.index') }}" class="font-medium text-brand-700">&larr; Volver al catálogo</a>
        </nav>

        <header>
            <p class="section-eyebrow">Edición</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Editar producto</h1>
            <p class="mt-2 max-w-2xl break-words text-sm leading-6 text-slate-600">
                {{ $product->nombre }} &middot; publicado por
                {{ $product->distribuidor->razon_social ?? 'Distribuidor' }}
            </p>
        </header>

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

        <form method="POST" action="{{ route('products.update', $product->id) }}" enctype="multipart/form-data"
              class="card mt-6" data-testid="producto-form">
            @csrf
            @method('PUT')

            <fieldset class="m-0 border-0 p-0">
                <legend class="text-base font-semibold text-slate-900">Identidad del producto</legend>

                <div class="mt-4">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $product->nombre) }}" required>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="categoria">Categoría</label>
                        <input id="categoria" type="text" name="categoria"
                               value="{{ old('categoria', $product->categoria) }}" required>
                    </div>
                    <div>
                        <label for="presentacion">Presentación</label>
                        <input id="presentacion" type="text" name="presentacion"
                               value="{{ old('presentacion', $product->presentacion) }}" required>
                    </div>
                </div>

                <div class="mt-4">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="4">{{ old('descripcion', $product->descripcion) }}</textarea>
                </div>
            </fieldset>

            <hr class="my-6 border-slate-200">

            <fieldset class="m-0 border-0 p-0">
                <legend class="text-base font-semibold text-slate-900">Empaque y precio</legend>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="unidades_por_bulto">Unidades por bulto</label>
                        <input id="unidades_por_bulto" type="number" min="1" step="1" name="unidades_por_bulto"
                               value="{{ old('unidades_por_bulto', $product->unidades_por_bulto) }}" required>
                    </div>
                    <div>
                        <label for="precio_bulto">Precio por bulto (S/)</label>
                        <input id="precio_bulto" type="number" min="0.01" step="0.01" name="precio_bulto"
                               value="{{ old('precio_bulto', $product->precio_bulto) }}" required>
                        <span class="field-hint">Máximo dos decimales. No incluye IGV.</span>
                    </div>
                    <div>
                        <label for="moq_cantidad_minima">Pedido mínimo (MOQ)</label>
                        <input id="moq_cantidad_minima" type="number" min="1" step="1" name="moq_cantidad_minima"
                               value="{{ old('moq_cantidad_minima', $product->moq_cantidad_minima) }}" required
                               data-moq-input>
                        <span class="field-hint">Bultos mínimos por cotización.</span>
                    </div>
                    <div>
                        <label for="stock_disponible">Stock disponible (bultos)</label>
                        <input id="stock_disponible" type="number" min="0" step="1" name="stock_disponible"
                               value="{{ old('stock_disponible', $product->stock_disponible) }}" required>
                        <span class="field-hint" data-stock-hint>
                            Debe ser <strong>0</strong> o al menos el pedido mínimo
                            (<strong data-moq-value>{{ old('moq_cantidad_minima', $product->moq_cantidad_minima) }}</strong> bultos).
                            Con 0 el producto aparece como <em>«Temporalmente sin stock»</em>.
                        </span>
                    </div>
                </div>
            </fieldset>

            <hr class="my-6 border-slate-200">

            @include('products._tramos', [
                'tramos' => $product->tramosPrecio->map(fn ($tramo): array => [
                    'cantidad_minima' => $tramo->cantidad_minima,
                    'precio_bulto' => $tramo->precio_bulto,
                ])->all(),
            ])

            <hr class="my-6 border-slate-200">

            <fieldset class="m-0 border-0 p-0">
                <legend class="text-base font-semibold text-slate-900">Imagen</legend>

                <div class="mt-4">
                    @if ($product->imagen_url)
                        <div class="mb-4 flex flex-wrap items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <img src="{{ Storage::disk('public')->url($product->imagen_url) }}"
                                 alt="{{ $product->nombre }}"
                                 class="h-24 w-24 rounded-lg border border-slate-200 object-cover">
                            <p class="m-0 min-w-0 break-words text-xs leading-5 text-slate-600">
                                Imagen actual: {{ $product->imagen_url }}
                            </p>
                        </div>
                    @endif

                    <label for="imagen">Imagen del producto</label>
                    <input id="imagen" type="file" name="imagen" accept="image/png,image/jpeg,image/webp">
                    <span class="field-hint">
                        PNG, JPG o WebP, máximo 2 MB. Subir una imagen reemplaza la actual.
                    </span>
                </div>
            </fieldset>

            <div class="mt-8 flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                <button class="btn sm:flex-1" type="submit" data-testid="producto-actualizar">
                    Actualizar producto
                </button>
                <a class="btn ghost" href="{{ route('products.index') }}">Cancelar</a>
            </div>
        </form>

        @unless ($product->is_active)
            <div class="card mt-6 border-amber-200 bg-amber-50" data-testid="producto-pausado">
                <h2 class="mt-0 text-lg font-semibold text-amber-900">Este producto está fuera del catálogo</h2>
                <p class="mt-2 text-sm leading-6 text-amber-900">
                    Está pausado, por lo que el catálogo público lo muestra atenuado y sin opción de
                    cotizar. Los cambios que guardes arriba se aplicarán, pero el producto no volverá
                    a ofrecerse hasta que lo republiques.
                </p>

                <form method="POST" action="{{ route('products.reactivate', $product->id) }}" class="mt-4">
                    @csrf
                    @method('PATCH')
                    <button class="btn" type="submit">Republicar en el catálogo</button>
                </form>
            </div>
        @endunless
    </div>

    {{-- La regla de stock depende del MOQ. Mantener el texto sincronizado evita
         que el usuario descubra el límite con un error de validación. --}}
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
