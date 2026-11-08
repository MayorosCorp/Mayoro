<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MicrositeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::prefix('products')->name('products.')->group(function () {
        // RN-02 / RN-03: solo el Empresario validado publica y administra productos.
        Route::middleware('role:empresario')->group(function () {
            Route::get('/create', [ProductController::class, 'create'])->name('create');
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
            Route::put('/{product}', [ProductController::class, 'update'])->name('update');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');
            Route::patch('/{product}/reactivar', [ProductController::class, 'reactivate'])->name('reactivate');
        });
    });

    Route::prefix('quotes')->name('quotes.')->group(function () {
        Route::get('/', [QuoteController::class, 'index'])->name('index');
        Route::get('/{quote}', [QuoteController::class, 'show'])->name('show')->whereNumber('quote');
    });

    Route::prefix('perfil')->name('profile.')->group(function () {
        Route::get('/empresario', [ProfileController::class, 'showEmpresarioForm'])->name('empresario');
        Route::post('/empresario', [ProfileController::class, 'convertirEnEmpresario'])->name('empresario.convertir');
    });

    // RN-03: la vitrina es privada de su dueno. La ruta no acepta id, por lo
    // que el servidor siempre edita la cuenta autenticada.
    Route::middleware('role:empresario')->group(function () {
        Route::get('/mi-empresa', [MicrositeController::class, 'edit'])->name('microsite.edit');
        Route::put('/mi-empresa', [MicrositeController::class, 'update'])->name('microsite.update');
    });

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/create', [InventoryController::class, 'create'])->name('create');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::get('/{inventory}/edit', [InventoryController::class, 'edit'])->name('edit');
        Route::put('/{inventory}', [InventoryController::class, 'update'])->name('update');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
    });
});

/*
|--------------------------------------------------------------------------
| Catálogo público (HU-03)
|--------------------------------------------------------------------------
| La vitrina mayorista es accesible sin credenciales: es el mecanismo de
| captación de bodegas. La restricción whereNumber evita que el comodín
| {product} capture rutas literales como /products/create.
*/
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/{product}', [ProductController::class, 'show'])->name('show')->whereNumber('product');
});

/*
|--------------------------------------------------------------------------
| Directorio de distribuidores (HU-03)
|--------------------------------------------------------------------------
| La ficha del distribuidor es parte de la captación pública de bodegas.
*/
Route::prefix('proveedores')->name('suppliers.')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show')->whereNumber('supplier');
});

/*
|--------------------------------------------------------------------------
| Micrositio del Empresario (RN-03)
|--------------------------------------------------------------------------
| Vitrina publica y personalizable de cada Empresario validado. El slug es la
| unica llave de acceso y se restringe a minusculas, numeros y guiones.
*/
Route::get('/empresas/{slug}', [MicrositeController::class, 'show'])
    ->name('microsite.show')
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');

/*
|--------------------------------------------------------------------------
| Cotizacion publica (HU-04 + RN-01 / RN-05)
|--------------------------------------------------------------------------
| Un visitante sin cuenta puede armar y generar su enlace de WhatsApp: Mayoro
| no le exige RUC para cotizar. La solicitud del visitante no se persiste; solo
| la Persona Natural autenticada conserva historial en cotizaciones_b2b.
| El limite de peticiones evita usar el formulario publico como vector de abuso.
*/
Route::prefix('quotes')->name('quotes.')->group(function () {
    Route::get('/create', [QuoteController::class, 'create'])->name('create');
    Route::post('/', [QuoteController::class, 'store'])->name('store')->middleware('throttle:20,1');
    Route::get('/efimera', [QuoteController::class, 'efimera'])->name('efimera');
});
