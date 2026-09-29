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
    /**
     * Ficha CI-COD-12: el catálogo público redirige al login conservando la URL
     * de origen (returnUrl) para devolver al visitante al producto que miraba.
     */
    public function showLoginForm(Request $request): View
    {
        $returnUrl = $this->sanearReturnUrl($request->query('returnUrl'));

        if ($returnUrl !== null) {
            $request->session()->put('url.intended', $returnUrl);
        }

        return view('auth.login', ['returnUrl' => $returnUrl]);
    }

    public function showRegisterForm(Request $request): View
    {
        $returnUrl = $this->sanearReturnUrl($request->query('returnUrl'));

        if ($returnUrl !== null) {
            $request->session()->put('returnUrl', $returnUrl);
        }

        return view('auth.register', ['returnUrl' => $returnUrl]);
    }

    /**
     * Solo se admite un returnUrl interno al dominio de la aplicación: evita
     * redirigir al usuario fuera del sitio (open redirect). Las URL absolutas
     * propias del dominio se normalizan a su ruta relativa.
     */
    private function sanearReturnUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, '//')) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $partes = parse_url($url);

        if ($partes === false || ! isset($partes['host'])) {
            return null;
        }

        $hostPropio = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        if (strcasecmp($partes['host'], $hostPropio) !== 0) {
            return null;
        }

        $ruta = $partes['path'] ?? '/';

        return isset($partes['query']) ? $ruta.'?'.$partes['query'] : $ruta;
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        /*
         * El modelo User apunta a usuarios_b2b, cuya columna de contacto es
         * email_contacto. No existe una columna `email`: consultarla produciría
         * un error SQL en lugar del mensaje de credenciales inválidas.
         */
        $autenticado = Auth::attempt(
            [
                'email_contacto' => $credentials['email'],
                'password' => $credentials['password'],
            ],
            $request->boolean('remember')
        );

        if (! $autenticado) {
            return back()->withErrors([
                'email' => __('auth.failed'),
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard.index'));
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

        $returnUrl = $this->sanearReturnUrl($request->session()->pull('returnUrl'));

        if ($returnUrl !== null) {
            return redirect($returnUrl)->with('success', 'Registro exitoso en Mayoro B2B.');
        }

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
