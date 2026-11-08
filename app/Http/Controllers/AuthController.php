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
            // La aplicación no tiene archivos de traducción y su locale es `en`:
            // __('auth.failed') se imprimía literalmente en el formulario. El
            // mensaje se declara en español, igual que el resto de la interfaz.
            return back()->withErrors([
                'email' => 'El correo o la contraseña no coinciden con una cuenta registrada.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard.index'));
    }

    public function register(Request $request): RedirectResponse
    {
        $messages = [
            'razon_social.required' => 'El nombre es obligatorio.',
            'email_contacto.required' => 'El correo electrónico es obligatorio.',
            'email_contacto.email' => 'El correo electrónico debe ser válido.',
            'email_contacto.unique' => 'El correo electrónico ingresado ya se encuentra registrado',
            'telefono_whatsapp.required' => 'El teléfono de WhatsApp es obligatorio.',
            'telefono_whatsapp.regex' => 'El teléfono debe contener 9 dígitos móviles.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];

        /*
         * RN-01: cualquier visitante se registra como Persona Natural. El RUC no
         * se pide aqui: solo se exige al convertir la cuenta en Empresario
         * (RN-02), porque ese es el rol que publica y responde cotizaciones.
         */
        $validated = $request->validate([
            'email_contacto' => ['required', 'email', 'unique:usuarios_b2b,email_contacto'],
            'telefono_whatsapp' => ['required', 'regex:/^(?:\+?51)?9\d{8}$/'],
            'razon_social' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $messages);

        // Formateo automático de prefijo internacional +51
        $phoneDigits = preg_replace('/\D/', '', $validated['telefono_whatsapp']);
        $formattedPhone = '+51'.substr($phoneDigits, -9);

        // Sanitización contra inyección de caracteres y XSS
        $name = strip_tags(trim($validated['razon_social']));
        $direccion = isset($validated['direccion']) ? strip_tags(trim($validated['direccion'])) : null;

        $user = User::create([
            'ruc_empresa' => null,
            'razon_social' => $name,
            'rubro' => null,
            'email_contacto' => strtolower(trim($validated['email_contacto'])),
            'telefono_whatsapp' => $formattedPhone,
            'direccion' => $direccion,
            'rol' => User::ROL_PERSONA_NATURAL,
            'password' => Hash::make($validated['password']),
            'is_premium' => false,
        ]);

        Auth::login($user);

        $returnUrl = $this->sanearReturnUrl($request->session()->pull('returnUrl'));

        $mensaje = 'Cuenta creada. Ya puedes cotizar; para vender, conviértete en Empresario.';

        if ($returnUrl !== null) {
            return redirect($returnUrl)->with('success', $mensaje);
        }

        return redirect()->route('dashboard.index')->with('success', $mensaje);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }
}
