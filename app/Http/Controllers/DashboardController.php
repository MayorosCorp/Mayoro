<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $esDistribuidor = $usuario->rol === 'distribuidor';

        return view('dashboard.index', [
            'metricas' => [
                // El distribuidor ve su propio catálogo; el bodeguero, el total
                // publicado en la plataforma, que es lo que le compete.
                'productos' => $esDistribuidor
                    ? $usuario->productos()->count()
                    : Product::where('is_active', true)->count(),
                'productosEtiqueta' => $esDistribuidor ? 'Productos publicados' : 'Productos en catálogo',
                'cotizaciones' => Quote::query()
                    ->where('estado', 'pendiente')
                    ->when($esDistribuidor, fn ($consulta) => $consulta->where('distribuidor_id', $usuario->id))
                    ->count(),
                'proveedores' => User::query()->where('rol', 'distribuidor')->count(),
            ],
            // La tabla ordenes_b2b aún no tiene modelo ni datos. Se declara
            // explicitamente para que la tarjeta muestre un guion en vez de un
            // cero que el usuario leería como "no tienes pedidos".
            'pedidosDisponibles' => false,
        ]);
    }
}
