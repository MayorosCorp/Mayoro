@extends('layouts.app')

@section('title', 'Registro B2B')

@section('content')
    <div class="mx-auto max-w-xl">
        <header>
            <p class="section-eyebrow">Red mayorista</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">
                Registro comercial B2B
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Únete a la red mayorista para bodegas y distribuidores. El RUC se valida con
                el dígito verificador del Módulo 11.
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

        <form method="POST" action="{{ route('auth.register.post') }}" class="card mt-6"
              data-testid="registro-form">
            @csrf

            <fieldset class="m-0 border-0 p-0">
                <legend class="text-base font-semibold text-slate-900">Tipo de comercio</legend>

                <div class="mt-4">
                    <label for="rol">Tipo de Comercio (Rol)</label>
                    <select id="rol" name="rol" required>
                        <option value="bodega" @selected(old('rol') === 'bodega')>
                            Comerciante Minorista (Bodega)
                        </option>
                        <option value="distribuidor" @selected(old('rol') === 'distribuidor')>
                            Distribuidor Mayorista
                        </option>
                    </select>
                    <span class="field-hint">
                        La bodega cotiza y compra; el distribuidor además publica productos.
                    </span>
                </div>
            </fieldset>

            <hr class="my-6 border-slate-200">

            <fieldset class="m-0 border-0 p-0">
                <legend class="text-base font-semibold text-slate-900">Datos del comercio</legend>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ruc_empresa">RUC (11 dígitos)</label>
                        <input id="ruc_empresa" type="text" name="ruc_empresa"
                               value="{{ old('ruc_empresa') }}" maxlength="11"
                               inputmode="numeric" placeholder="Ej. 20601234567" required>
                    </div>
                    <div>
                        <label for="telefono_whatsapp">Teléfono con WhatsApp</label>
                        <input id="telefono_whatsapp" type="text" name="telefono_whatsapp"
                               value="{{ old('telefono_whatsapp') }}" maxlength="12"
                               inputmode="tel" placeholder="Ej. 987654321" required>
                        <span class="field-hint">9 dígitos móviles; añadimos el prefijo +51.</span>
                    </div>
                </div>

                <div class="mt-4">
                    <label for="razon_social">Razón Social / Nombre Comercial</label>
                    <input id="razon_social" type="text" name="razon_social"
                           value="{{ old('razon_social') }}"
                           placeholder="Ej. Distribuidora Los Andes S.A.C." required>
                </div>

                <div class="mt-4">
                    <label for="email_contacto">Correo Electrónico de Contacto</label>
                    <input id="email_contacto" type="email" name="email_contacto"
                           value="{{ old('email_contacto') }}" autocomplete="email"
                           placeholder="contacto@empresa.pe" required>
                </div>

                <div class="mt-4">
                    <label for="direccion">Dirección Comercial (Opcional)</label>
                    <input id="direccion" type="text" name="direccion" value="{{ old('direccion') }}"
                           autocomplete="street-address" placeholder="Av. Los Comerciantes 123">
                </div>
            </fieldset>

            <hr class="my-6 border-slate-200">

            <fieldset class="m-0 border-0 p-0">
                <legend class="text-base font-semibold text-slate-900">Acceso</legend>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password">Contraseña (Mínimo 8 caracteres)</label>
                        <input id="password" type="password" name="password"
                               autocomplete="new-password" required>
                    </div>
                    <div>
                        <label for="password_confirmation">Confirmar Contraseña</label>
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               autocomplete="new-password" required>
                    </div>
                </div>
            </fieldset>

            <button class="btn mt-8 w-full" type="submit">Crear Cuenta Comercial</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            ¿Ya tienes cuenta comercial?
            <a href="{{ route('auth.login', array_filter(['returnUrl' => $returnUrl ?? null])) }}"
               class="font-semibold text-brand-700">Inicia sesión</a>
        </p>
    </div>
@endsection
