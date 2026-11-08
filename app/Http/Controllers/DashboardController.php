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
                // Cada rol ve solo las cotizaciones que le pertenecen: el
                // distribuidor las que recibio y el bodeguero las que genero.
                'cotizaciones' => Quote::query()
                    ->where('estado', 'generada')
                    ->when(
                        $esDistribuidor,
                        fn ($consulta) => $consulta->where('distribuidor_id', $usuario->id),
                        fn ($consulta) => $consulta->where('bodega_id', $usuario->id),
                    )
                    ->count(),
                'cotizacionesEtiqueta' => $esDistribuidor
                    ? 'Cotizaciones recibidas'
                    : 'Cotizaciones generadas',
                'proveedores' => User::query()->where('rol', 'distribuidor')->count(),
                // Un producto puede estar publicado y aun así no ser cotizable:
                // sin stock, con stock bajo el MOQ o pausado. Es la cifra que
                // el comercio necesita revisar, no un contador de pedidos de un
                // módulo que todavía no existe.
                'noCotizables' => Product::query()
                    ->when(
                        $esDistribuidor,
                        fn ($consulta) => $consulta->where('distribuidor_id', $usuario->id)
                            ->where(function ($interno): void {
                                $interno->where('is_active', false)
                                    ->orWhere('stock_disponible', '<=', 0)
                                    ->orWhereColumn('stock_disponible', '<', 'moq_cantidad_minima');
                            }),
                        fn ($consulta) => $consulta->where('is_active', true)
                            ->where(function ($interno): void {
                                $interno->where('stock_disponible', '<=', 0)
                                    ->orWhereColumn('stock_disponible', '<', 'moq_cantidad_minima');
                            }),
                    )
                    ->count(),
            ],
        ]);
    }
}
