<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\RucPeruano;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * RN-02: formulario de conversion de Persona Natural a Empresario.
     */
    public function showEmpresarioForm(Request $request): View|RedirectResponse
    {
        if ($request->user()->esEmpresario()) {
            return redirect()
                ->route('dashboard.index')
                ->with('success', 'Tu cuenta ya tiene el rol Empresario.');
        }

        return view('profile.empresario');
    }

    /**
     * Valida el RUC con Modulo 11 y habilita la publicacion de productos.
     */
    public function convertirEnEmpresario(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->esEmpresario()) {
            return redirect()
                ->route('dashboard.index')
                ->with('success', 'Tu cuenta ya tiene el rol Empresario.');
        }

        $messages = [
            'ruc_empresa.required' => 'El RUC es obligatorio para convertirte en Empresario.',
            'ruc_empresa.unique' => 'Ese RUC ya pertenece a otra cuenta de Mayoro.',
            'razon_social.required' => 'La razón social o nombre comercial es obligatorio.',
            'rubro.required' => 'Indica el rubro de tu negocio.',
            'telefono_whatsapp.required' => 'El teléfono comercial con WhatsApp es obligatorio.',
            'telefono_whatsapp.regex' => 'El teléfono debe contener 9 dígitos móviles.',
        ];

        $validated = $request->validate([
            'ruc_empresa' => [
                'required',
                new RucPeruano,
                Rule::unique('usuarios_b2b', 'ruc_empresa')->ignore($user->id),
            ],
            'razon_social' => ['required', 'string', 'max:255'],
            'rubro' => ['required', 'string', 'max:120'],
            'telefono_whatsapp' => ['required', 'regex:/^(?:\+?51)?9\d{8}$/'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ], $messages);

        $phoneDigits = preg_replace('/\D/', '', $validated['telefono_whatsapp']);

        $user->forceFill([
            'ruc_empresa' => $validated['ruc_empresa'],
            'razon_social' => strip_tags(trim($validated['razon_social'])),
            'slug' => $user->slug ?: User::generarSlugMicrositio($validated['razon_social']),
            'rubro' => strip_tags(trim($validated['rubro'])),
            'telefono_whatsapp' => '+51'.substr($phoneDigits, -9),
            'direccion' => isset($validated['direccion'])
                ? strip_tags(trim($validated['direccion']))
                : $user->direccion,
            'rol' => User::ROL_EMPRESARIO,
        ])->save();

        return redirect()
            ->route('dashboard.index')
            ->with('success', 'Ahora eres Empresario: ya puedes publicar productos y recibir cotizaciones.');
    }
}
