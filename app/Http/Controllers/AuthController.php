<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Autenticar por email_contacto o por email
        $attemptB2B = [
            'email_contacto' => $credentials['email'],
            'password' => $credentials['password'],
        ];

        if (! Auth::attempt($attemptB2B, $request->boolean('remember'))) {
            if (! Auth::attempt($credentials, $request->boolean('remember'))) {
                return back()->withErrors([
                    'email' => __('auth.failed'),
                ])->onlyInput('email');
            }
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard.index'));
    }

    public function showRegisterForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $messages = [
            'ruc_empresa.required' => 'El RUC es obligatorio.',
            'ruc_empresa.regex' => 'Formato de RUC inválido',
            'ruc_empresa.unique' => 'El RUC o correo electrónico ingresado ya se encuentra registrado',
            'email_contacto.required' => 'El correo electrónico es obligatorio.',
            'email_contacto.email' => 'El correo electrónico debe ser válido.',
            'email_contacto.unique' => 'El RUC o correo electrónico ingresado ya se encuentra registrado',
            'telefono_whatsapp.required' => 'El teléfono de WhatsApp es obligatorio.',
            'telefono_whatsapp.regex' => 'El teléfono debe contener 9 dígitos móviles.',
            'razon_social.required' => 'La razón social es obligatoria.',
            'rol.required' => 'Debe seleccionar un rol comercial.',
            'rol.in' => 'El rol seleccionado no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];

        $validated = $request->validate([
            'ruc_empresa' => ['required', 'regex:/^\d{11}$/', 'unique:usuarios_b2b,ruc_empresa'],
            'email_contacto' => ['required', 'email', 'unique:usuarios_b2b,email_contacto'],
            'telefono_whatsapp' => ['required', 'regex:/^(?:\+?51)?9\d{8}$/'],
            'razon_social' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'rol' => ['required', 'in:bodega,distribuidor'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $messages);

        // Formateo automático de prefijo internacional +51
        $phoneDigits = preg_replace('/\D/', '', $validated['telefono_whatsapp']);
        $formattedPhone = '+51'.substr($phoneDigits, -9);

        // Sanitización contra inyección de caracteres y XSS
        $razonSocial = strip_tags(trim($validated['razon_social']));
        $direccion = isset($validated['direccion']) ? strip_tags(trim($validated['direccion'])) : null;

        $user = User::create([
            'ruc_empresa' => $validated['ruc_empresa'],
            'razon_social' => $razonSocial,
            'email_contacto' => strtolower(trim($validated['email_contacto'])),
            'telefono_whatsapp' => $formattedPhone,
            'direccion' => $direccion,
            'rol' => $validated['rol'],
            'password' => Hash::make($validated['password']),
            'is_premium' => false,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard.index')->with('success', 'Registro exitoso en Mayoro B2B.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }
}
