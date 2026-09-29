<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Portada pública de Mayoro (HU-03): vitrina de acceso sin autenticación.
     */
    public function index(): View
    {
        return view('welcome', [
            'destacados' => $this->productosDestacados(),
            'metricas' => $this->metricasPlataforma(),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    private function productosDestacados(): Collection
    {
        if (! Schema::hasTable('productos_mayoristas')) {
            return collect();
        }

        return Product::with('distribuidor')
            ->where('is_active', true)
            ->latest()
            ->limit(6)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    private function metricasPlataforma(): array
    {
        if (! Schema::hasTable('productos_mayoristas') || ! Schema::hasTable('usuarios_b2b')) {
            return ['productos' => 0, 'distribuidores' => 0, 'bodegas' => 0];
        }

        return [
            'productos' => (int) Product::where('is_active', true)->count(),
            'distribuidores' => (int) User::where('rol', 'distribuidor')->count(),
            'bodegas' => (int) User::where('rol', 'bodega')->count(),
        ];
    }
}
