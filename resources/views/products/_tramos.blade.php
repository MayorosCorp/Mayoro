@php
    /*
     * RN-04: editor de tramos por volumen compartido por el alta y la edicion.
     * Los tramos llegan ya ordenados por umbral; el formulario permite anadir
     * y quitar filas, y una fila completamente vacia se descarta en el
     * servidor para que no cuente como tramo.
     */
    $tramosEditor = collect(old('tramos', $tramos ?? []))
        ->map(fn ($tramo): array => [
            'cantidad_minima' => $tramo['cantidad_minima'] ?? '',
            'precio_bulto' => $tramo['precio_bulto'] ?? '',
        ])
        ->values();

    if ($tramosEditor->isEmpty()) {
        $tramosEditor = collect([['cantidad_minima' => '', 'precio_bulto' => '']]);
    }

    $siguienteIndice = (int) $tramosEditor->keys()->max() + 1;
@endphp

<fieldset class="m-0 border-0 p-0" data-testid="producto-tramos-editor">
    <legend class="text-base font-semibold text-slate-900">Descuentos por volumen (opcional)</legend>

    <p class="mt-2 text-sm leading-6 text-slate-600">
        Define desde cuántos bultos baja el precio. Cada tramo debe empezar por encima del
        MOQ y ofrecer un precio menor al base y al del tramo anterior. En una cotización se
        aplica un solo tramo: el de mayor umbral que no supere la cantidad pedida.
    </p>

    <div class="mt-4 space-y-3" data-tramos-lista>
        @foreach ($tramosEditor as $indice => $tramo)
            <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                 data-tramo-row>
                <div>
                    <label for="tramo-cantidad-{{ $indice }}">Desde (bultos)</label>
                    <input id="tramo-cantidad-{{ $indice }}" type="number" min="1" step="1"
                           name="tramos[{{ $indice }}][cantidad_minima]"
                           value="{{ $tramo['cantidad_minima'] }}"
                           placeholder="Ej. 50" data-tramo-cantidad>
                    @error('tramos.'.$indice.'.cantidad_minima')
                        <span class="mt-1 block text-xs font-medium text-red-700">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="tramo-precio-{{ $indice }}">Precio por bulto (S/)</label>
                    <input id="tramo-precio-{{ $indice }}" type="number" min="0.01" step="0.01"
                           name="tramos[{{ $indice }}][precio_bulto]"
                           value="{{ $tramo['precio_bulto'] }}"
                           placeholder="Ej. 110.00" data-tramo-precio>
                    @error('tramos.'.$indice.'.precio_bulto')
                        <span class="mt-1 block text-xs font-medium text-red-700">{{ $message }}</span>
                    @enderror
                </div>

                <button type="button"
                        class="btn ghost min-h-0 px-3 py-2 text-xs"
                        data-tramo-eliminar>
                    Quitar
                </button>
            </div>
        @endforeach
    </div>

    <button type="button" class="btn ghost mt-3 min-h-0 px-3 py-2 text-xs" data-tramo-agregar>
        + Agregar tramo
    </button>
</fieldset>

<template data-tramo-plantilla>
    <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
         data-tramo-row>
        <div>
            <label for="tramo-cantidad-__INDICE__">Desde (bultos)</label>
            <input id="tramo-cantidad-__INDICE__" type="number" min="1" step="1"
                   name="tramos[__INDICE__][cantidad_minima]" value=""
                   placeholder="Ej. 50" data-tramo-cantidad>
        </div>
        <div>
            <label for="tramo-precio-__INDICE__">Precio por bulto (S/)</label>
            <input id="tramo-precio-__INDICE__" type="number" min="0.01" step="0.01"
                   name="tramos[__INDICE__][precio_bulto]" value=""
                   placeholder="Ej. 110.00" data-tramo-precio>
        </div>
        <button type="button" class="btn ghost min-h-0 px-3 py-2 text-xs" data-tramo-eliminar>
            Quitar
        </button>
    </div>
</template>

<script>
    (function () {
        var lista = document.querySelector('[data-tramos-lista]');
        var agregar = document.querySelector('[data-tramo-agregar]');
        var plantilla = document.querySelector('[data-tramo-plantilla]');

        if (! lista || ! agregar || ! plantilla) {
            return;
        }

        var indice = {{ $siguienteIndice }};

        agregar.addEventListener('click', function () {
            var fragmento = plantilla.content.cloneNode(true);

            fragmento.querySelectorAll('[name], [id], [for]').forEach(function (elemento) {
                ['name', 'id', 'for'].forEach(function (atributo) {
                    var valor = elemento.getAttribute(atributo);
                    if (valor) {
                        elemento.setAttribute(atributo, valor.replace(/__INDICE__/g, String(indice)));
                    }
                });
            });

            lista.appendChild(fragmento);
            indice++;

            var ultimaFila = lista.querySelector('[data-tramo-row]:last-child');
            if (ultimaFila) {
                var campo = ultimaFila.querySelector('[data-tramo-cantidad]');
                if (campo) {
                    campo.focus();
                }
            }
        });

        lista.addEventListener('click', function (evento) {
            var boton = evento.target.closest('[data-tramo-eliminar]');
            if (! boton) {
                return;
            }

            var fila = boton.closest('[data-tramo-row]');
            if (fila) {
                fila.remove();
            }
        });
    })();
</script>
