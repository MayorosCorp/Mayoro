<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Services\ProductImageStorage;
use App\Services\QuotationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Ficha HU-03: el catálogo público pagina de 12 en 12 registros.
     */
    public const POR_PAGINA = 12;

    public function __construct(
        private ProductImageStorage $imagenes
    ) {}

    /**
     * Catálogo público (HU-03): accesible sin autenticación, con búsqueda por
     * texto, filtro por categoría, paginación de 12 y desglose de IGV 18%.
     */
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('q', ''));
        $categoria = trim((string) $request->query('categoria', ''));

        $products = Product::with('distribuidor')
            ->when($busqueda !== '', function (Builder $query) use ($busqueda): void {
                $query->where(function (Builder $interno) use ($busqueda): void {
                    $interno->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('descripcion', 'like', "%{$busqueda}%")
                        ->orWhere('presentacion', 'like', "%{$busqueda}%")
                        ->orWhereHas('distribuidor', function (Builder $distribuidor) use ($busqueda): void {
                            $distribuidor->where('razon_social', 'like', "%{$busqueda}%");
                        });
                });
            })
            ->when($categoria !== '', fn (Builder $query) => $query->where('categoria', $categoria))
            ->orderByDesc('is_active')
            ->latest()
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $categorias = Product::query()
            ->where('is_active', true)
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        return view('products.index', [
            'products' => $products,
            'categorias' => $categorias,
            'busqueda' => $busqueda,
            'categoria' => $categoria,
        ]);
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

        $imagenUrl = $request->hasFile('imagen')
            ? $this->imagenes->store($request->file('imagen'))
            : null;

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
            'imagen_url' => $imagenUrl,
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

    /**
     * Detalle público del producto (HU-03). Solo expone productos activos.
     */
    /**
     * Detalle público del producto.
     *
     * No se filtra por is_active a propósito: HU-03 exige que un producto
     * pausado siga visible en el catálogo, opaco y con la leyenda "Temporalmente
     * sin stock". Como la cuadrícula lo enlaza, filtrar aquí devolvía un 404
     * sobre un enlace que el propio catálogo ofrecía, y dejaba inalcanzable la
     * rama de la vista que explica la situation al bodeguero.
     *
     * Un identificador inexistente sigue produciendo 404 mediante findOrFail.
     */
    public function show(string|int $product): View
    {
        $productModel = is_numeric($product)
            ? Product::with('distribuidor')->findOrFail($product)
            : $product;

        $unitario = $productModel->precio_unitario_calculado;

        return view('products.show', [
            'product' => $productModel,
            'igv' => round($unitario * QuotationService::IGV_RATE, 2),
            'precioConIgv' => round($unitario * (1 + QuotationService::IGV_RATE), 2),
        ]);
    }

    /**
     * El formulario de edición también exige propiedad: sin este control un
     * distribuidor podía abrir el formulario de otro catálogo y solo fallaría
     * al guardar, con un error en lugar de un 403 claro.
     */
    public function edit(Request $request, string|int $product): View
    {
        $productModel = $this->buscarProductoPropio($request, $product);

        return view('products.edit', ['product' => $productModel->load('distribuidor')]);
    }

    public function update(StoreProductRequest $request, string|int $product): RedirectResponse
    {
        $productModel = $this->buscarProductoPropio($request, $product);
        $validated = $request->validated();

        $unidades = (int) $validated['unidades_por_bulto'];
        $precioBulto = (float) $validated['precio_bulto'];

        $imagenAnterior = $productModel->imagen_url;

        if ($request->hasFile('imagen')) {
            $productModel->imagen_url = $this->imagenes->store($request->file('imagen'));
            $productModel->save();
            $this->imagenes->eliminar($imagenAnterior);
        }

        $productModel->update([
            'nombre' => $this->sanitizeInput($validated['nombre']),
            'categoria' => $this->sanitizeInput($validated['categoria']),
            'presentacion' => $this->sanitizeInput($validated['presentacion']),
            'descripcion' => isset($validated['descripcion']) ? $this->sanitizeInput($validated['descripcion']) : null,
            'unidades_por_bulto' => $unidades,
            'precio_bulto' => $precioBulto,
            'precio_unitario_sugerido' => round($precioBulto / $unidades, 2),
            'moq_cantidad_minima' => (int) $validated['moq_cantidad_minima'],
            'stock_disponible' => max(0, (int) $request->integer('stock_disponible')),
        ]);

        return redirect()->route('products.show', $productModel->id)
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Request $request, string|int $product): RedirectResponse
    {
        $productModel = $this->buscarProductoPropio($request, $product);
        $productModel->update(['is_active' => false]);

        return redirect()->route('products.index')
            ->with('success', 'Producto dado de baja del catálogo.');
    }

    /**
     * Reintegra un producto pausado al catálogo.
     *
     * La baja es una decisión del distribuidor y debe ser reversible. Se expone
     * como acción explícita y no como efecto colateral de guardar una edición:
     * guardar los datos de un producto pausado no debe resucitarlo sin que el
     * usuario lo haya pedido, porque el catálogo público volvería a ofrecerlo.
     *
     * Si el producto sigue sin existencias, reactivarlo lo deja en estado
     * sin_stock y el catálogo lo mostrará con la leyenda correspondiente, que es
     * el resultado honesto: el distribuidor también puede ajustar el stock desde
     * la pantalla de edición antes de republicar.
     */
    public function reactivate(Request $request, string|int $product): RedirectResponse
    {
        $productModel = $this->buscarProductoPropio($request, $product);

        if ($productModel->is_active) {
            return redirect()->route('products.show', $productModel->id)
                ->with('success', 'El producto ya estaba activo en el catálogo.');
        }

        $productModel->update(['is_active' => true]);

        return redirect()->route('products.show', $productModel->id)
            ->with('success', 'Producto republicado en el catálogo mayorista.');
    }

    /**
     * Un distribuidor solo puede administrar los productos que él publicó.
     */
    private function buscarProductoPropio(Request $request, string|int $product): Product
    {
        $productModel = is_numeric($product) ? Product::findOrFail($product) : $product;

        abort_unless(
            $productModel->distribuidor_id === $request->user()->id,
            403,
            'Solo puede administrar los productos que publico'
        );

        return $productModel;
    }
}
