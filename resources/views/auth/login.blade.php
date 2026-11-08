@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="mx-auto max-w-md">
        <header class="text-center">
            <p class="section-eyebrow">Acceso comercial</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Iniciar sesión</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Entra con el correo de contacto de tu comercio registrado.
            </p>
        </header>

        @if (! empty($returnUrl))
            <div class="surface-note mt-6" role="status">
                Inicia sesión para continuar con tu cotización. Te devolvemos a la página
                que estabas viendo.
            </div>
        @endif

        @if ($errors->any())
            <div class="flash error mt-6" role="alert">
                <div>
                    <p class="m-0 font-semibold">No pudimos iniciar sesión</p>
                    <ul class="error-list mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('auth.login.post') }}" class="card mt-6" data-testid="login-form">
            @csrf

            <div>
                <label for="email">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       autocomplete="email" required autofocus>
            </div>

            <div class="mt-4">
                <label for="password">Contraseña</label>
                <input id="password" type="password" name="password"
                       autocomplete="current-password" required>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm font-normal text-slate-700">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300">
                Mantener la sesión abierta
            </label>

            <button class="btn mt-6 w-full" type="submit">Entrar</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            ¿Aún no tienes cuenta?
            <a href="{{ route('auth.register', array_filter(['returnUrl' => $returnUrl ?? null])) }}"
               class="font-semibold text-brand-700">Regístrate</a>
        </p>
    </div>
@endsection
