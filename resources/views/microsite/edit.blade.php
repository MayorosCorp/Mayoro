@extends('layouts.app')

@section('title', 'Mi empresa')

@section('content')
    @php
        $colorActual = old('color_primario', $empresario->color_primario ?: '#12305c');
        $monograma = collect(preg_split('/\s+/', trim($empresario->razon_social)) ?: [])
            ->reject(fn (string $palabra): bool => in_array(\Illuminate\Support\Str::lower($palabra), ['de', 'en', 'con', 'la', 'el', 'y', 's.a.c.', 's.a.', 'e.i.r.l.', 's.r.l.', 'sac'], true))
            ->reject(fn (string $palabra): bool => preg_match('/^\d/', $palabra) === 1)
            ->take(2)
            ->map(fn (string $palabra): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($palabra, 0, 1)))
            ->implode('');
        $monograma = $monograma !== '' ? $monograma : 'EM';
        $paletas = [
            '#12305c' => 'Azul Mayoro',
            '#0f4c5c' => 'Petróleo',
            '#166534' => 'Verde comercial',
            '#7c2d12' => 'Terracota',
            '#9a3412' => 'Naranja tostado',
            '#334155' => 'Grafito',
            '#78350f' => 'Café',
            '#f59e0b' => 'Ámbar claro',
        ];
    @endphp

    <div class="mx-auto max-w-6xl">
        <nav class="mb-4 text-sm" aria-label="Migas de pan">
            <a href="{{ route('dashboard.index') }}" class="font-medium text-brand-700">&larr; Volver al dashboard</a>
        </nav>

        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="m-0 text-3xl font-semibold tracking-tight text-slate-900">Mi empresa</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Tu vitrina pública vive en
                    <span class="font-semibold text-slate-800">/empresas/{{ $empresario->slug }}</span>.
                    El catálogo se arma solo con tus productos activos; aquí defines cómo se presenta tu marca.
                </p>
            </div>

            <a class="btn ghost shrink-0" href="{{ route('microsite.show', $empresario->slug) }}"
               target="_blank" rel="noopener" data-testid="micrositio-ver">
                Ver micrositio publicado
            </a>
        </header>

        <div class="microsite mt-6 grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_360px]"
             style="--ms-brand: {{ $colorActual }}; --ms-brand-ink: {{ $empresario->color_primario_texto }};">
            <form method="POST" action="{{ route('microsite.update') }}" class="card m-0"
                  data-testid="micrositio-form">
                @csrf
                @method('PUT')

                <fieldset class="m-0 border-0 p-0">
                    <legend class="text-base font-semibold text-slate-900">Dirección pública</legend>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        Es el enlace que compartes con tus clientes. Usa el nombre de tu empresa, corto y fácil de escribir.
                    </p>

                    <div class="mt-4">
                        <label for="slug">Dirección de tu micrositio</label>
                        <div class="flex items-stretch overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/20">
                            <span class="inline-flex shrink-0 items-center border-r border-slate-200 bg-slate-50 px-3 text-sm text-slate-500">
                                /empresas/
                            </span>
                            <input id="slug" type="text" name="slug"
                                   value="{{ old('slug', $empresario->slug) }}"
                                   maxlength="80" required
                                   pattern="[a-z0-9]+(-[a-z0-9]+)*"
                                   class="!rounded-none !border-0 !shadow-none"
                                   data-testid="micrositio-slug">
                        </div>
                        <span class="field-hint">
                            Solo minúsculas, números y guiones. Ejemplo: <em>distribuidora-andina</em>.
                        </span>
                    </div>
                </fieldset>

                <hr class="my-6 border-slate-200">

                <fieldset class="m-0 border-0 p-0">
                    <legend class="text-base font-semibold text-slate-900">Presentación comercial</legend>

                    <div class="mt-4">
                        <label for="descripcion">Descripción de tu empresa</label>
                        <textarea id="descripcion" name="descripcion" rows="5" maxlength="600"
                                  placeholder="Qué distribuyes, a qué tipo de bodegas atiendes y qué te diferencia..."
                                  data-testid="micrositio-descripcion">{{ old('descripcion', $empresario->descripcion) }}</textarea>
                        <span class="field-hint">Máximo 600 caracteres. Aparece bajo el nombre de tu empresa.</span>
                    </div>

                    <div class="mt-4">
                        <label for="horario_atencion">Horario de atención</label>
                        <input id="horario_atencion" type="text" name="horario_atencion"
                               value="{{ old('horario_atencion', $empresario->horario_atencion) }}"
                               maxlength="120" placeholder="Lun a Vie, 8:00 a 18:00"
                               data-testid="micrositio-horario">
                        <span class="field-hint">Si lo dejas vacío, la vitrina indicará que se coordina por WhatsApp.</span>
                    </div>
                </fieldset>

                <hr class="my-6 border-slate-200">

                <fieldset class="m-0 border-0 p-0">
                    <legend class="text-base font-semibold text-slate-900">Color de marca</legend>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        Tiñe el membrete, los botones de cotización y el fondo de los productos sin fotografía.
                        El texto cambia automáticamente a claro u oscuro para mantenerse legible.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-4">
                        <div>
                            <label for="color_primario">Color primario</label>
                            <input id="color_primario" type="color" name="color_primario"
                                   value="{{ $colorActual }}"
                                   class="h-12 w-20 cursor-pointer rounded-lg border border-slate-300 bg-white p-1"
                                   data-testid="micrositio-color">
                        </div>
                        <div class="min-w-0">
                            <span class="block text-sm font-semibold text-slate-700">Color elegido</span>
                            <output class="mt-1 block font-mono text-sm uppercase text-slate-600" data-color-value>
                                {{ $colorActual }}
                            </output>
                        </div>
                    </div>

                    <span class="mt-4 block text-xs font-semibold uppercase tracking-wide text-slate-500">Paletas rápidas</span>
                    <div class="ms-swatches" role="group" aria-label="Paletas rápidas">
                        @foreach ($paletas as $hex => $nombre)
                            <button type="button" class="ms-swatch" style="background-color: {{ $hex }};"
                                    title="{{ $nombre }} ({{ $hex }})" aria-label="Usar {{ $nombre }}"
                                    data-color-preset="{{ $hex }}"></button>
                        @endforeach
                    </div>
                </fieldset>

                <div class="mt-8 flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                    <button class="btn sm:flex-1" type="submit" data-testid="micrositio-guardar">
                        Guardar micrositio
                    </button>
                    <a class="btn ghost" href="{{ route('microsite.show', $empresario->slug) }}"
                       target="_blank" rel="noopener">
                        Ver cambios publicados
                    </a>
                </div>
            </form>

            <aside class="space-y-4 lg:sticky lg:top-24">
                <section class="card m-0" data-testid="micrositio-previa">
                    <h2 class="m-0 text-base font-semibold text-slate-900">Vista previa del membrete</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Así se verá la cabecera con el color seleccionado.
                    </p>

                    <div class="ms-preview mt-4" data-previa data-monogram="{{ $monograma }}"
                         style="background-color: {{ $colorActual }}; color: {{ $empresario->color_primario_texto }};">
                        <span class="ms-badge">
                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-3.5 w-3.5">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/>
                            </svg>
                            Empresario validado
                        </span>
                        <p class="ms-preview__name">{{ $empresario->razon_social }}</p>
                        @if ($empresario->rubro)
                            <p class="ms-preview__rubro">{{ $empresario->rubro }}</p>
                        @endif
                    </div>

                    <dl class="mt-4 space-y-2 text-xs leading-5 text-slate-600">
                        <div class="flex justify-between gap-3">
                            <dt>Monograma</dt>
                            <dd class="m-0 font-semibold text-slate-800">{{ $monograma }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt>RUC</dt>
                            <dd class="m-0 font-semibold text-slate-800">{{ $empresario->ruc_empresa ?: 'No publicado' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt>WhatsApp</dt>
                            <dd class="m-0 font-semibold text-slate-800">{{ $empresario->telefono_whatsapp ?: 'No publicado' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="card m-0 border-brand-200 bg-brand-50">
                    <h2 class="m-0 text-base font-semibold text-brand-900">Qué comparte tu micrositio</h2>
                    <ul class="mt-2 space-y-2 text-sm leading-6 text-brand-900">
                        <li>Solo tu catálogo activo, nunca productos de otros Empresarios.</li>
                        <li>Precio por bulto, MOQ, stock y tramos por volumen.</li>
                        <li>Tu WhatsApp comercial: la negociación se cierra fuera de Mayoro.</li>
                    </ul>
                </section>

                <a class="btn ghost w-full" href="{{ route('dashboard.index') }}">Volver al dashboard</a>
            </aside>
        </div>
    </div>

    <script>
        (function () {
            var color = document.querySelector('[data-testid="micrositio-color"]');
            var previa = document.querySelector('[data-previa]');
            var valor = document.querySelector('[data-color-value]');
            var lienzo = document.querySelector('.microsite');
            var presets = document.querySelectorAll('[data-color-preset]');

            if (! color || ! previa) { return; }

            function aplicarContraste() {
                var hex = color.value;
                previa.style.backgroundColor = hex;

                var rojo = parseInt(hex.substr(1, 2), 16);
                var verde = parseInt(hex.substr(3, 2), 16);
                var azul = parseInt(hex.substr(5, 2), 16);
                var luminancia = (rojo * 299 + verde * 587 + azul * 114) / 1000;
                var tinta = luminancia >= 150 ? '#0f172a' : '#ffffff';

                previa.style.color = tinta;

                if (lienzo) {
                    lienzo.style.setProperty('--ms-brand', hex);
                    lienzo.style.setProperty('--ms-brand-ink', tinta);
                }

                if (valor) {
                    valor.textContent = hex;
                }
            }

            color.addEventListener('input', aplicarContraste);

            presets.forEach(function (preset) {
                preset.addEventListener('click', function () {
                    color.value = preset.dataset.colorPreset;
                    aplicarContraste();
                });
            });
        })();
    </script>
@endsection
