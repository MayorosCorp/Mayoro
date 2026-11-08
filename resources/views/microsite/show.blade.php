@extends('layouts.app')

@section('title', $empresario->razon_social)
@section('meta_description', \Illuminate\Support\Str::limit($empresario->descripcion ?: 'Catálogo mayorista de '.$empresario->razon_social.' con precio por bulto, pedido mínimo y cotización directa por WhatsApp.', 155))

@section('content')
    @php
        $whatsapp = preg_replace('/\D/', '', (string) $empresario->telefono_whatsapp);
        $mensajeWhatsApp = rawurlencode('Hola '.$empresario->razon_social.', vi su micrositio en Mayoro y quiero consultar por su catálogo mayorista.');

        // Monograma tipografico: da identidad a la empresa y al producto sin
        // inventar logotipos ni recurrir a un rectangulo gris de "sin imagen".
        $monograma = function (string $texto, string $respaldo = 'PR'): string {
            $conectores = ['de', 'en', 'con', 'la', 'el', 'y', 's.a.c.', 's.a.', 'e.i.r.l.', 's.r.l.', 'sac', 'kg', 'g', 'ml', 'l', 'lt', 'lts', 'x'];
            $palabras = collect(preg_split('/\s+/', trim($texto)) ?: [])
                ->reject(fn (string $palabra): bool => in_array(\Illuminate\Support\Str::lower($palabra), $conectores, true))
                ->reject(fn (string $palabra): bool => preg_match('/^\d/', $palabra) === 1)
                ->values();

            if ($palabras->count() === 1) {
                return \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($palabras->first(), 0, 2));
            }

            $letras = $palabras->take(2)
                ->map(fn (string $palabra): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($palabra, 0, 1)))
                ->implode('');

            return $letras !== '' ? $letras : $respaldo;
        };

        $monogramaEmpresa = $monograma($empresario->razon_social, 'EM');
    @endphp

    <div class="microsite" style="--ms-brand: {{ $empresario->color_primario ?: '#12305c' }}; --ms-brand-ink: {{ $empresario->color_primario_texto }};">
        <nav class="mb-5 text-sm" aria-label="Migas de pan">
            <a href="{{ route('suppliers.index') }}" class="font-medium text-brand-700">&larr; Volver a distribuidores</a>
        </nav>

        <section class="ms-hero" data-monogram="{{ $monogramaEmpresa }}" data-testid="micrositio-hero">
            <div class="ms-hero__top">
                <div class="ms-identity">
                    <span class="ms-monogram" aria-hidden="true">{{ $monogramaEmpresa }}</span>

                    <div class="min-w-0">
                        <div class="ms-badges">
                            <span class="ms-badge">
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.6 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/>
                                </svg>
                                Empresario validado
                            </span>

                            @if ($empresario->is_premium)
                                <span class="ms-badge">Distribuidor Premium</span>
                            @endif
                        </div>

                        <h1>{{ $empresario->razon_social }}</h1>

                        @if ($empresario->rubro)
                            <p class="ms-rubro">{{ $empresario->rubro }}</p>
                        @endif
                    </div>
                </div>

                <div class="ms-hero__actions">
                    @if ($whatsapp !== '')
                        <a class="ms-whatsapp"
                           href="https://wa.me/{{ $whatsapp }}?text={{ $mensajeWhatsApp }}"
                           target="_blank" rel="noopener"
                           data-testid="micrositio-whatsapp">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12.04 2a9.9 9.9 0 0 0-8.5 14.96L2 22l5.2-1.5A9.9 9.9 0 1 0 12.04 2Zm0 1.8a8.1 8.1 0 1 1-4.13 15.07l-.3-.18-3.06.88.9-2.98-.2-.31A8.1 8.1 0 0 1 12.04 3.8Zm-3.1 3.62c-.2 0-.5.07-.76.35-.26.28-1 .98-1 2.4 0 1.4 1.03 2.77 1.17 2.96.14.2 2 3.2 4.94 4.36 2.44.96 2.94.77 3.47.72.53-.05 1.7-.7 1.94-1.36.24-.67.24-1.24.17-1.36-.07-.12-.26-.2-.55-.35-.28-.14-1.7-.84-1.96-.94-.26-.1-.45-.14-.64.15-.19.28-.73.93-.9 1.12-.16.2-.33.21-.61.07-.28-.14-1.2-.44-2.28-1.4a8.6 8.6 0 0 1-1.58-1.96c-.16-.28-.02-.43.12-.57.13-.13.29-.33.43-.5.14-.16.19-.28.28-.47.1-.19.05-.35-.02-.5-.07-.14-.62-1.53-.86-2.08-.22-.54-.45-.5-.62-.5h-.5Z"/>
                            </svg>
                            Escribir por WhatsApp
                        </a>
                        <p class="ms-phone">{{ $empresario->telefono_whatsapp }}</p>
                    @endif

                    @auth
                        @if (auth()->id() === $empresario->id)
                            <a class="ms-edit" href="{{ route('microsite.edit') }}" data-testid="micrositio-editar">
                                Editar mi empresa
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            @if ($empresario->descripcion)
                <p class="ms-description">{{ $empresario->descripcion }}</p>
            @endif

            <dl class="ms-facts">
                <div>
                    <dt>RUC</dt>
                    <dd>{{ $empresario->ruc_empresa ?: 'No publicado' }}</dd>
                </div>
                <div>
                    <dt>Horario de atención</dt>
                    <dd>{{ $empresario->horario_atencion ?: 'Coordinar por WhatsApp' }}</dd>
                </div>
                <div>
                    <dt>Dirección</dt>
                    <dd>{{ $empresario->direccion ?: 'Atención coordinada' }}</dd>
                </div>
                <div>
                    <dt>Catálogo activo</dt>
                    <dd>{{ $empresario->productos_count }} producto(s) para cotizar</dd>
                </div>
            </dl>
        </section>

        <div class="ms-catalog-head">
            <div>
                <h2>Catálogo mayorista</h2>
                <p>
                    Precio por bulto, pedido mínimo y stock a la vista. Cotiza sin cuenta ni RUC:
                    Mayoro prepara el resumen y tú lo envías al distribuidor por WhatsApp.
                </p>
            </div>

            <form method="GET" action="{{ route('microsite.show', $empresario->slug) }}"
                  class="ms-search" role="search">
                <div class="ms-search-field">
                    <label for="q" class="sr-only">Buscar en este micrositio</label>
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.4l3.07 3.08a1 1 0 0 1-1.41 1.41l-3.08-3.07A7 7 0 0 1 2 9Z" clip-rule="evenodd"/>
                    </svg>
                    <input id="q" type="search" name="q" value="{{ $busqueda }}"
                           placeholder="Buscar producto, categoría...">
                </div>
                <button class="btn" type="submit">Buscar</button>
                @if ($busqueda !== '')
                    <a class="btn ghost" href="{{ route('microsite.show', $empresario->slug) }}">Limpiar</a>
                @endif
            </form>
        </div>

        @if ($busqueda !== '')
            <p class="ms-results">
                <strong class="text-slate-800">{{ $productos->total() }}</strong>
                resultado(s) para «{{ $busqueda }}» dentro de este catálogo.
            </p>
        @endif

        <div class="ms-grid" data-testid="micrositio-catalogo">
            @forelse ($productos as $producto)
                <article class="ms-card {{ $producto->esta_agotado ? 'is-unavailable' : '' }}"
                         data-testid="producto-card">
                    <a class="ms-card__media" href="{{ route('products.show', $producto->id) }}"
                       aria-label="Ver {{ $producto->nombre }}">
                        @if ($producto->imagen_url)
                            <img src="{{ Storage::disk('public')->url($producto->imagen_url) }}" alt="{{ $producto->nombre }}"
                                 loading="lazy">
                        @else
                            <span class="ms-card__monogram" aria-hidden="true">{{ $monograma($producto->nombre) }}</span>
                        @endif

                        <span class="ms-card__category">{{ $producto->categoria }}</span>
                    </a>

                    <div class="ms-card__body">
                        <h3 class="ms-card__title">
                            <a href="{{ route('products.show', $producto->id) }}">{{ $producto->nombre }}</a>
                        </h3>

                        <p class="ms-card__presentation">
                            {{ $producto->presentacion }} &middot; {{ $producto->unidades_por_bulto }} unidades por bulto
                        </p>

                        <p class="ms-card__price">
                            <strong>S/ {{ number_format((float) $producto->precio_bulto, 2) }}</strong>
                            <span>/ bulto</span>
                        </p>
                        <p class="ms-card__unit">
                            S/ {{ number_format($producto->precio_unitario_calculado, 2) }} por unidad, sin IGV
                        </p>

                        @if ($producto->tramosPrecio->isNotEmpty())
                            @php $mejorTramo = $producto->tramosPrecio->last(); @endphp
                            <p class="ms-card__tier" data-testid="producto-tramo">
                                Desde {{ $mejorTramo->cantidad_minima }} bultos:
                                S/ {{ number_format((float) $mejorTramo->precio_bulto, 2) }} / bulto
                            </p>
                        @endif

                        <div class="ms-card__meta">
                            <span>MOQ <strong>{{ $producto->moq_cantidad_minima }} bultos</strong></span>

                            @if ($producto->esta_agotado)
                                <span class="ms-stock-off" data-testid="producto-estado">{{ $producto->etiqueta_estado_stock }}</span>
                            @else
                                <span class="ms-stock-ok" data-testid="producto-estado">
                                    Stock <strong>{{ $producto->stock_disponible }} bultos</strong>
                                </span>
                            @endif
                        </div>

                        <div class="ms-card__action">
                            @if ($producto->esta_agotado)
                                <span class="btn disabled" aria-disabled="true" data-testid="producto-sin-stock">
                                    Temporalmente sin stock
                                </span>
                            @elseif (! Auth::check() || Auth::user()->esPersonaNatural())
                                <a class="btn ms-btn-brand" href="{{ route('quotes.create', ['producto' => $producto->id]) }}"
                                   data-testid="producto-cotizar">
                                    Cotizar pedido
                                </a>
                            @else
                                <a class="btn ghost" href="{{ route('products.show', $producto->id) }}">
                                    Ver detalle
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="ms-empty">
                    <h3>
                        @if ($busqueda !== '')
                            Sin resultados dentro de este micrositio
                        @else
                            Este micrositio aún no tiene productos activos
                        @endif
                    </h3>
                    <p>
                        @if ($busqueda !== '')
                            Prueba con otro nombre o
                            <a href="{{ route('microsite.show', $empresario->slug) }}">mira el catálogo completo</a>.
                        @else
                            Vuelve pronto o revisa el
                            <a href="{{ route('products.index') }}">catálogo general de Mayoro</a>.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        @if ($productos->hasPages())
            <div class="mt-8">{{ $productos->links() }}</div>
        @endif
    </div>
@endsection
