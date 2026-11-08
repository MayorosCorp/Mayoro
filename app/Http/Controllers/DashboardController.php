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
        $esEmpresario = $usuario->esEmpresario();

        return view('dashboard.index', [
            'metricas' => [
                // El Empresario ve su propio catalogo; la Persona Natural, el
                // total publicado en la plataforma, que es lo que le compete.
                'productos' => $esEmpresario
                    ? $usuario->productos()->count()
                    : Product::where('is_active', true)->count(),
                'productosEtiqueta' => $esEmpresario ? 'Productos publicados' : 'Productos en catálogo',
                // Cada rol ve solo las cotizaciones que le pertenecen: el
                // Empresario las que recibio y la Persona Natural las que genero.
                'cotizaciones' => Quote::query()
                    ->where('estado', 'generada')
                    ->when(
                        $esEmpresario,
                        fn ($consulta) => $consulta->where('distribuidor_id', $usuario->id),
                        fn ($consulta) => $consulta->where('bodega_id', $usuario->id),
                    )
                    ->count(),
                'cotizacionesEtiqueta' => $esEmpresario
                    ? 'Cotizaciones recibidas'
                    : 'Cotizaciones generadas',
                'proveedores' => User::query()->where('rol', User::ROL_EMPRESARIO)->count(),
                // Un producto puede estar publicado y aun asi no ser cotizable:
                // sin stock, con stock bajo el MOQ o pausado. Es la cifra que
                // el comercio necesita revisar, no un contador de pedidos de un
                // modulo que todavia no existe.
                'noCotizables' => Product::query()
                    ->when(
                        $esEmpresario,
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
