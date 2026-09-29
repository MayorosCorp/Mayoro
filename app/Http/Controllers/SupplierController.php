<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    /**
     * Ficha CI-COD-14: el módulo de proveedores se apoya en los comercios
     * reales de usuarios_b2b con rol distribuidor. No existe una tabla
     * `proveedores`: el proveedor mayorista es el propio comercio distribuidor.
     */
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('q', ''));

        $distribuidores = User::query()
            ->where('rol', 'distribuidor')
            ->withCount(['productos' => fn (Builder $query) => $query->where('is_active', true)])
            ->when($busqueda !== '', function (Builder $query) use ($busqueda): void {
                $query->where(function (Builder $interno) use ($busqueda): void {
                    $interno->where('razon_social', 'like', "%{$busqueda}%")
                        ->orWhere('ruc_empresa', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('razon_social')
            ->paginate(12)
            ->withQueryString();

        return view('suppliers.index', [
            'distribuidores' => $distribuidores,
            'busqueda' => $busqueda,
        ]);
    }

    /**
     * Ficha pública de un distribuidor con su catálogo mayorista vigente.
     */
    public function show(string $supplier): View
    {
        $distribuidor = User::query()
            ->where('rol', 'distribuidor')
            ->withCount(['productos' => fn (Builder $query) => $query->where('is_active', true)])
            ->findOrFail($supplier);

        $productos = Product::query()
            ->where('distribuidor_id', $distribuidor->id)
            ->where('is_active', true)
            ->orderBy('nombre')
            ->paginate(12);

        return view('suppliers.show', [
            'supplier' => $distribuidor,
            'productos' => $productos,
        ]);
    }
}
