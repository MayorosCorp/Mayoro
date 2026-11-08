<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MicrositeController extends Controller
{
    /**
     * RN-03: la vitrina del Empresario pagina igual que el catalogo general.
     */
    public const POR_PAGINA = 12;

    /**
     * Slugs que chocarian con rutas fijas de Mayoro o que se prestan a
     * suplantar al propio marketplace.
     */
    private const SLUGS_RESERVADOS = [
        'admin',
        'api',
        'auth',
        'cotizaciones',
        'dashboard',
        'empresas',
        'empresarios',
        'login',
        'mi-empresa',
        'perfil',
        'productos',
        'products',
        'proveedores',
        'quotes',
        'register',
        'suppliers',
    ];

    /**
     * Pagina publica del Empresario. Solo resuelve cuentas con rol Empresario:
     * una Persona natural nunca expone un micrositio aunque tenga un slug.
     */
    public function show(Request $request, string $slug): View
    {
        $empresario = User::query()
            ->where('rol', User::ROL_EMPRESARIO)
            ->where('slug', $slug)
            ->withCount(['productos' => fn (QueryBuilder $query) => $query->where('is_active', true)])
            ->firstOrFail();

        $busqueda = trim((string) $request->query('q', ''));

        $productos = Product::query()
            ->with('tramosPrecio')
            ->where('distribuidor_id', $empresario->id)
            ->where('is_active', true)
            ->when($busqueda !== '', function (Builder $query) use ($busqueda): void {
                $query->where(function (Builder $interno) use ($busqueda): void {
                    $interno->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('categoria', 'like', "%{$busqueda}%")
                        ->orWhere('presentacion', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('microsite.show', [
            'empresario' => $empresario,
            'productos' => $productos,
            'busqueda' => $busqueda,
        ]);
    }

    /**
     * Edicion del micrositio propio. No recibe un id: el servidor siempre usa
     * la cuenta autenticada, de modo que un Empresario no puede editar la
     * vitrina de otro.
     */
    public function edit(Request $request): View
    {
        $empresario = $this->empresarioConSlug($request);

        return view('microsite.edit', ['empresario' => $empresario]);
    }

    public function update(Request $request): RedirectResponse
    {
        $empresario = $this->empresarioConSlug($request);

        $validated = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('usuarios_b2b', 'slug')->ignore($empresario->id),
                Rule::notIn(self::SLUGS_RESERVADOS),
            ],
            'descripcion' => ['nullable', 'string', 'max:600'],
            'color_primario' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'horario_atencion' => ['nullable', 'string', 'max:120'],
        ], [
            'slug.required' => 'Define la dirección pública de tu empresa.',
            'slug.regex' => 'La dirección solo admite minúsculas, números y guiones (por ejemplo: distribuidora-andina).',
            'slug.unique' => 'Esa dirección ya pertenece a otro micrositio. Elige otra.',
            'slug.not_in' => 'Esa dirección está reservada por Mayoro. Elige otra.',
            'descripcion.max' => 'La descripción no puede superar los 600 caracteres.',
            'color_primario.required' => 'Elige un color primario para tu micrositio.',
            'color_primario.regex' => 'El color debe tener el formato hexadecimal #RRGGBB.',
            'horario_atencion.max' => 'El horario no puede superar los 120 caracteres.',
        ]);

        $empresario->forceFill([
            'slug' => $validated['slug'],
            'descripcion' => isset($validated['descripcion'])
                ? strip_tags(trim($validated['descripcion']))
                : null,
            'color_primario' => strtolower($validated['color_primario']),
            'horario_atencion' => isset($validated['horario_atencion'])
                ? strip_tags(trim($validated['horario_atencion']))
                : null,
        ])->save();

        return redirect()
            ->route('microsite.edit')
            ->with('success', 'Micrositio actualizado. Ya puedes compartir tu vitrina pública.');
    }

    /**
     * Garantiza que la cuenta tenga slug antes de mostrar el editor, incluso
     * si el Empresario viene de una migracion o se creo sin pasar por el
     * formulario de conversion.
     */
    private function empresarioConSlug(Request $request): User
    {
        $empresario = $request->user();

        if (blank($empresario->slug)) {
            $empresario->forceFill([
                'slug' => User::generarSlugMicrositio($empresario->razon_social, $empresario->id),
            ])->save();
        }

        return $empresario;
    }
}
