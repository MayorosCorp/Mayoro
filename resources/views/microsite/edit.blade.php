@extends('layouts.app')

@section('title', 'Mi empresa')

@section('content')
    <div class="mx-auto max-w-4xl">
        <nav class="mb-4 text-sm" aria-label="Migas de pan">
            <a href="{{ route('dashboard.index') }}" class="font-medium text-brand-700">&larr; Volver al dashboard</a>
        </nav>

        <header>
            <p class="section-eyebrow">Micrositio público</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Mi empresa</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Esta es la vitrina que verán los compradores en
                <span class="font-semibold text-slate-800">/empresas/{{ $empresario->slug }}</span>.
                Su catálogo se arma automáticamente con los productos activos que publicas.
            </p>
        </header>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
            <form method="POST" action="{{ route('microsite.update') }}" class="card"
                  data-testid="micrositio-form">
                @csrf
                @method('PUT')

                <fieldset class="m-0 border-0 p-0">
                    <legend class="text-base font-semibold text-slate-900">Dirección y presentación</legend>

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

                    <div class="mt-4">
                        <label for="descripcion">Descripción de tu empresa</label>
                        <textarea id="descripcion" name="descripcion" rows="5" maxlength="600"
                                  placeholder="Cuenta qué distribuyes, a quién atiendes y qué te diferencia..."
                                  data-testid="micrositio-descripcion">{{ old('descripcion', $empresario->descripcion) }}</textarea>
                        <span class="field-hint">Máximo 600 caracteres. Se muestra en el encabezado de tu vitrina.</span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="color_primario">Color primario</label>
                            <div class="flex items-center gap-3">
                                <input id="color_primario" type="color" name="color_primario"
                                       value="{{ old('color_primario', $empresario->color_primario ?: '#23497b') }}"
                                       class="h-11 w-16 cursor-pointer rounded-lg border border-slate-300 bg-white p-1"
                                       data-testid="micrositio-color">
                                <span class="text-sm text-slate-600">Tiñe el encabezado de tu micrositio.</span>
                            </div>
                            <span class="field-hint">Usamos texto claro u oscuro automáticamente para mantenerlo legible.</span>
                        </div>

                        <div>
                            <label for="horario_atencion">Horario de atención</label>
                            <input id="horario_atencion" type="text" name="horario_atencion"
                                   value="{{ old('horario_atencion', $empresario->horario_atencion) }}"
                                   maxlength="120" placeholder="Lun a Vie, 8:00 a 18:00"
                                   data-testid="micrositio-horario">
                        </div>
                    </div>
                </fieldset>

                <div class="mt-8 flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                    <button class="btn sm:flex-1" type="submit" data-testid="micrositio-guardar">
                        Guardar micrositio
                    </button>
                    <a class="btn ghost" href="{{ route('microsite.show', $empresario->slug) }}"
                       target="_blank" rel="noopener" data-testid="micrositio-ver">
                        Ver mi micrositio
                    </a>
                </div>
            </form>

            <aside class="space-y-4">
                <section class="card" data-testid="micrositio-previa">
                    <p class="section-eyebrow">Vista previa del encabezado</p>

                    <div class="mt-3 overflow-hidden rounded-xl p-5" data-previa
                         style="background-color: {{ old('color_primario', $empresario->color_primario ?: '#23497b') }}; color: {{ $empresario->color_primario_texto }};">
                        <p class="m-0 text-xs font-semibold uppercase tracking-wide opacity-85">Empresario validado</p>
                        <p class="mb-0 mt-2 break-words text-xl font-semibold leading-snug">
                            {{ $empresario->razon_social }}
                        </p>
                        @if ($empresario->rubro)
                            <p class="mb-0 mt-1 text-sm opacity-90">{{ $empresario->rubro }}</p>
                        @endif
                    </div>

                    <p class="mb-0 mt-3 text-xs leading-5 text-slate-500">
                        El catálogo, el WhatsApp, el RUC y la dirección salen de los datos de tu cuenta.
                        Los productos pausados no aparecen en el micrositio.
                    </p>
                </section>

                <section class="card border-brand-200 bg-brand-50">
                    <h2 class="mt-0 text-base font-semibold text-brand-900">Qué comparte tu micrositio</h2>
                    <ul class="mt-2 space-y-2 text-sm leading-6 text-brand-900">
                        <li>Solo tu catálogo activo, sin productos de otros Empresarios.</li>
                        <li>Precio por bulto, MOQ y stock disponibles para cotizar.</li>
                        <li>Tu WhatsApp comercial, para cerrar la conversación fuera de Mayoro.</li>
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
            if (! color || ! previa) { return; }

            function aplicarContraste() {
                var hex = color.value;
                previa.style.backgroundColor = hex;

                var rojo = parseInt(hex.substr(1, 2), 16);
                var verde = parseInt(hex.substr(3, 2), 16);
                var azul = parseInt(hex.substr(5, 2), 16);
                var luminancia = (rojo * 299 + verde * 587 + azul * 114) / 1000;

                previa.style.color = luminancia >= 150 ? '#0f172a' : '#ffffff';
            }

            color.addEventListener('input', aplicarContraste);
        })();
    </script>
@endsection
