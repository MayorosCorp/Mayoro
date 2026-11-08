@extends('layouts.app')

@section('title', 'Convertirme en Empresario')

@section('content')
    @php($usuario = auth()->user())

    <nav class="mb-4 text-sm" aria-label="Migas de pan">
        <a href="{{ route('dashboard.index') }}" class="font-medium text-brand-700">&larr; Volver al dashboard</a>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
        <section class="card">
            <p class="section-eyebrow">Validación fiscal</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">
                Convertirme en Empresario
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Tu cuenta sigue siendo <strong>Persona natural</strong> y puede cotizar sin RUC. Para publicar
                productos y recibir cotizaciones necesitamos validar tu RUC con el dígito verificador del
                Módulo 11.
            </p>

            @if ($errors->any())
                <div class="flash error mt-6" role="alert">
                    <div>
                        <p class="m-0 font-semibold">Revisa los datos del negocio:</p>
                        <ul class="error-list mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('profile.empresario.convertir') }}" class="mt-6"
                  data-testid="empresario-form">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ruc_empresa">RUC (11 dígitos)</label>
                        <input id="ruc_empresa" type="text" name="ruc_empresa"
                               value="{{ old('ruc_empresa', $usuario->ruc_empresa) }}" maxlength="11"
                               inputmode="numeric" placeholder="Ej. 20123456786" required>
                        <span class="field-hint">Debe empezar con 10 o 20 y superar el Módulo 11.</span>
                    </div>
                    <div>
                        <label for="telefono_whatsapp">Teléfono comercial con WhatsApp</label>
                        <input id="telefono_whatsapp" type="text" name="telefono_whatsapp"
                               value="{{ old('telefono_whatsapp', $usuario->telefono_whatsapp) }}" maxlength="12"
                               inputmode="tel" placeholder="Ej. 987654321" required>
                        <span class="field-hint">9 dígitos móviles; añadimos el prefijo +51.</span>
                    </div>
                </div>

                <div class="mt-4">
                    <label for="razon_social">Razón social o nombre comercial</label>
                    <input id="razon_social" type="text" name="razon_social"
                           value="{{ old('razon_social', $usuario->razon_social) }}"
                           placeholder="Ej. Distribuidora Los Andes S.A.C." required>
                </div>

                <div class="mt-4">
                    <label for="rubro">Rubro del negocio</label>
                    <input id="rubro" type="text" name="rubro" value="{{ old('rubro', $usuario->rubro) }}"
                           placeholder="Ej. Abarrotes, bebidas y limpieza" required>
                    <span class="field-hint">Ayuda a los compradores a ubicar tu catálogo en el directorio.</span>
                </div>

                <div class="mt-4">
                    <label for="direccion">Dirección comercial (opcional)</label>
                    <input id="direccion" type="text" name="direccion"
                           value="{{ old('direccion', $usuario->direccion) }}" autocomplete="street-address"
                           placeholder="Av. Los Comerciantes 123">
                </div>

                <div class="mt-6 flex flex-wrap gap-2">
                    <button class="btn" type="submit" data-testid="empresario-convertir">
                        Validar RUC y convertirme en Empresario
                    </button>
                    <a class="btn ghost" href="{{ route('dashboard.index') }}">Ahora no</a>
                </div>
            </form>
        </section>

        <aside class="space-y-4">
            <section class="card border-brand-200 bg-brand-50">
                <h2 class="mt-0 text-lg font-semibold text-brand-900">Qué cambia con el rol Empresario</h2>
                <ul class="mt-3 space-y-2 text-sm leading-6 text-brand-900">
                    <li>Publicas productos con precio por bulto, MOQ y stock.</li>
                    <li>Administras tu catálogo y tu futura vitrina corporativa.</li>
                    <li>Recibes las cotizaciones que generan los compradores.</li>
                    <li>Tu RUC identifica al negocio en el directorio de Mayoro.</li>
                </ul>
            </section>

            <section class="card">
                <h2 class="mt-0 text-base font-semibold text-slate-900">Qué no cambia</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Seguirás cotizando como Persona natural cuando quieras comprar. Mayoro sigue sin procesar
                    pagos, envíos ni devoluciones: la negociación se cierra por WhatsApp.
                </p>
                <p class="surface-note mt-4">
                    Si el RUC no supera la validación del Módulo 11, no se modifica ningún dato de tu cuenta.
                </p>
            </section>
        </aside>
    </div>
@endsection
