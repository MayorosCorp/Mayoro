@extends('layouts.app')

@section('title', 'Crear cuenta')

@section('content')
    <div class="mx-auto max-w-xl">
        <header>
            <p class="section-eyebrow">Acceso libre</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">
                Crea tu cuenta en Mayoro
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Empieza como <strong>Persona natural</strong>: explora el catálogo mayorista, compara precios
                por bulto y cotiza por WhatsApp sin RUC. Si luego quieres vender, conviértete en Empresario
                desde tu perfil.
            </p>
        </header>

        <div class="surface-note mt-6" role="status">
            El RUC solo se pide al convertirte en Empresario. Nadie te bloquea el catálogo por no tenerlo.
        </div>

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
                <legend class="text-base font-semibold text-slate-900">Tus datos</legend>

                <div class="mt-4">
                    <label for="razon_social">Nombre completo</label>
                    <input id="razon_social" type="text" name="razon_social"
                           value="{{ old('razon_social') }}" autocomplete="name"
                           placeholder="Ej. María Fernanda Quispe" required>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="email_contacto">Correo electrónico</label>
                        <input id="email_contacto" type="email" name="email_contacto"
                               value="{{ old('email_contacto') }}" autocomplete="email"
                               placeholder="contacto@correo.pe" required>
                    </div>
                    <div>
                        <label for="telefono_whatsapp">Teléfono con WhatsApp</label>
                        <input id="telefono_whatsapp" type="text" name="telefono_whatsapp"
                               value="{{ old('telefono_whatsapp') }}" maxlength="12"
                               inputmode="tel" autocomplete="tel" placeholder="Ej. 987654321" required>
                        <span class="field-hint">9 dígitos móviles; añadimos el prefijo +51.</span>
                    </div>
                </div>

                <div class="mt-4">
                    <label for="direccion">Dirección (opcional)</label>
                    <input id="direccion" type="text" name="direccion" value="{{ old('direccion') }}"
                           autocomplete="street-address" placeholder="Av. Los Comerciantes 123">
                </div>
            </fieldset>

            <hr class="my-6 border-slate-200">

            <fieldset class="m-0 border-0 p-0">
                <legend class="text-base font-semibold text-slate-900">Acceso</legend>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password">Contraseña (mínimo 8 caracteres)</label>
                        <input id="password" type="password" name="password"
                               autocomplete="new-password" required>
                    </div>
                    <div>
                        <label for="password_confirmation">Confirmar contraseña</label>
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               autocomplete="new-password" required>
                    </div>
                </div>
            </fieldset>

            <button class="btn mt-8 w-full" type="submit" data-testid="registro-enviar">
                Crear cuenta
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            ¿Ya tienes cuenta?
            <a href="{{ route('auth.login', array_filter(['returnUrl' => $returnUrl ?? null])) }}"
               class="font-semibold text-brand-700">Inicia sesión</a>
        </p>
    </div>
@endsection
