@extends('layouts.app')

@section('title', 'Registro B2B')

@section('content')
    <div class="card" style="max-width:520px; margin:0 auto;">
        <h1>Registro Comercial B2B</h1>
        <p style="color:#666; margin-bottom:1.5rem; font-size:0.95rem;">
            Únete a la red mayorista para bodegas y distribuidores.
        </p>

        @if ($errors->any())
            <div style="background:#fee2e2; border:1px solid #ef4444; color:#b91c1c; padding:0.75rem 1rem; border-radius:6px; margin-bottom:1.5rem;">
                <ul style="margin:0; padding-left:1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('auth.register.post') }}">
            @csrf

            <label for="rol">Tipo de Comercio (Rol)</label>
            <select id="rol" name="rol" required style="width:100%; padding:0.5rem; margin-bottom:1rem; border:1px solid #ccc; border-radius:4px;">
                <option value="bodega" {{ old('rol') === 'bodega' ? 'selected' : '' }}>Comerciante Minorista (Bodega)</option>
                <option value="distribuidor" {{ old('rol') === 'distribuidor' ? 'selected' : '' }}>Distribuidor Mayorista</option>
            </select>

            <label for="ruc_empresa">RUC (11 dígitos)</label>
            <input id="ruc_empresa" type="text" name="ruc_empresa" value="{{ old('ruc_empresa') }}" maxlength="11" placeholder="Ej. 20601234567" required>

            <label for="razon_social">Razón Social / Nombre Comercial</label>
            <input id="razon_social" type="text" name="razon_social" value="{{ old('razon_social') }}" placeholder="Ej. Distribuidora Los Andes S.A.C." required>

            <label for="email_contacto">Correo Electrónico de Contacto</label>
            <input id="email_contacto" type="email" name="email_contacto" value="{{ old('email_contacto') }}" placeholder="contacto@empresa.pe" required>

            <label for="telefono_whatsapp">Teléfono con WhatsApp (9 dígitos móviles)</label>
            <input id="telefono_whatsapp" type="text" name="telefono_whatsapp" value="{{ old('telefono_whatsapp') }}" maxlength="12" placeholder="Ej. 987654321" required>

            <label for="direccion">Dirección Comercial (Opcional)</label>
            <input id="direccion" type="text" name="direccion" value="{{ old('direccion') }}" placeholder="Av. Los Comerciantes 123">

            <label for="password">Contraseña (Mínimo 8 caracteres)</label>
            <input id="password" type="password" name="password" required>

            <label for="password_confirmation">Confirmar Contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>

            <div style="margin-top:1.5rem;">
                <button class="btn" type="submit" style="width:100%;">Crear Cuenta Comercial</button>
            </div>
        </form>

        <p style="margin-top:1.2rem; font-size:.9rem; text-align:center;">
            ¿Ya tienes cuenta comercial? <a href="{{ route('auth.login') }}">Inicia sesión</a>
        </p>
    </div>
@endsection