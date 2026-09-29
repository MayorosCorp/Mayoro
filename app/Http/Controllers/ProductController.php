<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with('distribuidor')
            ->where('is_active', true)
            ->latest()
            ->paginate(15);

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $precioBulto = (float) $validated['precio_bulto'];
        $unidades = (int) $validated['unidades_por_bulto'];
        $precioUnitario = round($precioBulto / $unidades, 2);

        $nombre = $this->sanitizeInput($validated['nombre']);
        $categoria = $this->sanitizeInput($validated['categoria']);
        $presentacion = $this->sanitizeInput($validated['presentacion']);
        $descripcion = isset($validated['descripcion']) ? $this->sanitizeInput($validated['descripcion']) : null;

        Product::create([
            'distribuidor_id' => $request->user()->id,
            'nombre' => $nombre,
            'categoria' => $categoria,
            'presentacion' => $presentacion,
            'unidades_por_bulto' => $unidades,
            'precio_bulto' => $precioBulto,
            'moq_cantidad_minima' => (int) $validated['moq_cantidad_minima'],
            'precio_unitario_sugerido' => $precioUnitario,
            'descripcion' => $descripcion,
            'imagen_url' => $validated['imagen_url'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('products.index')->with('success', 'Producto publicado exitosamente en el catálogo mayorista.');
    }

    /**
     * Sanitiza cadenas de texto eliminando etiquetas script y código malicioso.
     */
    private function sanitizeInput(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value);

        return trim(strip_tags((string) $sanitized));
    }

    public function show(string|int $product): View
    {
        $productModel = is_numeric($product) ? Product::with('distribuidor')->findOrFail($product) : $product;

        return view('products.show', ['product' => $productModel]);
    }

    public function edit(string|int $product): View
    {
        $productModel = is_numeric($product) ? Product::findOrFail($product) : $product;

        return view('products.edit', ['product' => $productModel]);
    }

    public function update(Request $request, string|int $product): RedirectResponse
    {
        return redirect()->route('products.index');
    }

    public function destroy(string|int $product): RedirectResponse
    {
        return redirect()->route('products.index');
    }
}
