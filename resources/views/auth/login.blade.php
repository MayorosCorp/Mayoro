@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="card" style="max-width:420px; margin:0 auto;">
        <h1>Iniciar sesión</h1>

        @if (! empty($returnUrl))
            <div class="flash" style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; margin-bottom:1rem;">
                Inicia sesión para continuar con tu cotización. Te devolvemos a la página
                que estabas viendo.
            </div>
        @endif

        <form method="POST" action="{{ route('auth.login.post') }}">
            @csrf

            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required>

            <div style="margin-top:1.2rem;">
                <button class="btn" type="submit">Entrar</button>
            </div>
        </form>

        <p style="margin-top:1.2rem; font-size:.9rem;">
            ¿Aún no tienes cuenta?
            <a href="{{ route('auth.register', array_filter(['returnUrl' => $returnUrl ?? null])) }}">Regístrate</a>
        </p>
    </div>
@endsection
