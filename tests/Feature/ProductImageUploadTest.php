<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $distribuidor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->distribuidor = User::create([
            'ruc_empresa' => '20601234565',
            'razon_social' => 'Distribuidora Central S.A.C.',
            'email_contacto' => 'ventas@central.pe',
            'telefono_whatsapp' => '+51 987 654 321',
            'rol' => 'empresario',
            'password' => bcrypt('password123'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'nombre' => 'Aceite Primor 1L x 12',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 120.00,
            'moq_cantidad_minima' => 5,
            'stock_disponible' => 240,
        ], $extra);
    }

    /**
     * Criterio de aceptación: la imagen se carga al servidor y se almacena la
     * ruta en la base de datos.
     */

    // php artisan test --filter=test_almacena_la_imagen_en_el_disco_publico_y_registra_la_ruta
    public function test_almacena_la_imagen_en_el_disco_publico_y_registra_la_ruta(): void
    {
        // Given: un distribuidor autenticado crea un producto con una imagen PNG válida
        // When: envía el formulario de publicación con la imagen adjunta
        $response = $this->actingAs($this->distribuidor)->post(
            route('products.store'),
            $this->payload(['imagen' => UploadedFile::fake()->image('cerveza.png', 400, 400)])
        );

        // Then: la imagen se guarda en el disco public y su ruta queda registrada en la base de datos
        $response->assertRedirect(route('products.index'));

        $producto = Product::sole();

        $this->assertNotNull($producto->imagen_url);
        $this->assertStringStartsWith('productos/', $producto->imagen_url);
        $this->assertStringEndsWith('.png', $producto->imagen_url);

        Storage::disk('public')->assertExists($producto->imagen_url);
    }

    /**
     * Criterio de aceptación: el nombre del archivo queda saneado por el
     * servidor y no conserva el nombre original del cliente.
     */

    // php artisan test --filter=test_sanitiza_el_nombre_del_archivo_almacenado
    public function test_sanitiza_el_nombre_del_archivo_almacenado(): void
    {
        // Given: un distribuidor autenticado sube una imagen con un nombre de archivo malicioso
        // When: envía el formulario de publicación con ese archivo
        $this->actingAs($this->distribuidor)->post(
            route('products.store'),
            $this->payload([
                'imagen' => UploadedFile::fake()->image('../../etc/passwd cartoon!.png', 300, 300),
            ])
        );

        // Then: la ruta almacenada queda saneada, sin secuencias de traversal ni caracteres peligrosos
        $ruta = Product::sole()->imagen_url;

        $this->assertStringNotContainsString('..', $ruta);
        $this->assertStringNotContainsString('\\', $ruta);
        $this->assertStringNotContainsString('!', $ruta);
        $this->assertStringStartsWith('productos/', $ruta);
    }

    /**
     * Criterio de aceptación: se admiten PNG, JPG y WebP.
     */

    // php artisan test --filter=test_acepta_los_formatos_png_jpg_y_webp
    public function test_acepta_los_formatos_png_jpg_y_webp(): void
    {
        // Given: un distribuidor autenticado publica una imagen en cada formato admitido
        foreach (['png', 'jpg', 'webp'] as $formato) {
            // When: envía el formulario con una imagen en el formato en curso
            $this->actingAs($this->distribuidor)->post(
                route('products.store'),
                $this->payload([
                    'nombre' => 'Producto '.$formato,
                    'imagen' => UploadedFile::fake()->image('producto.'.$formato, 200, 200),
                ])
            );

            // Then: el producto se crea y su ruta de imagen queda registrada
            $this->assertDatabaseHas('productos_mayoristas', [
                'nombre' => 'Producto '.$formato,
            ]);
            $this->assertNotNull(Product::where('nombre', 'Producto '.$formato)->sole()->imagen_url);
        }
    }

    /**
     * Criterio de aceptación: se rechaza un formato no admitido.
     */

    // php artisan test --filter=test_rechaza_formatos_no_admitidos
    public function test_rechaza_formatos_no_admitidos(): void
    {
        // Given: un distribuidor autenticado adjunta un archivo PDF no admitido
        // When: envía el formulario de publicación con ese archivo
        $response = $this->actingAs($this->distribuidor)->post(
            route('products.store'),
            $this->payload(['imagen' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')])
        );

        // Then: se muestran errores de validación y no se persiste ningún producto
        $response->assertSessionHasErrors('imagen');
        $this->assertDatabaseCount('productos_mayoristas', 0);
    }

    /**
     * Criterio de aceptación: se rechaza un archivo que excede los 2 MB.
     */

    // php artisan test --filter=test_rechaza_imagenes_que_superan_los_2_mb
    public function test_rechaza_imagenes_que_superan_los_2_mb(): void
    {
        // Given: un distribuidor autenticado adjunta una imagen de 3000 KB, superior al límite permitido
        // When: envía el formulario de publicación con esa imagen
        $response = $this->actingAs($this->distribuidor)->post(
            route('products.store'),
            $this->payload(['imagen' => UploadedFile::fake()->image('enorme.png')->size(3000)])
        );

        // Then: se muestran errores de validación indicando el límite de 2 MB y no se persiste ningún producto
        $response->assertSessionHasErrors('imagen');
        $this->assertDatabaseCount('productos_mayoristas', 0);

        $error = session('errors')->getBag('default')->first('imagen');
        $this->assertStringContainsString('2 MB', $error);
    }

    /**
     * La imagen es opcional: el producto se publica sin ella.
     */

    // php artisan test --filter=test_la_imagen_es_opcional
    public function test_la_imagen_es_opcional(): void
    {
        // Given: un distribuidor autenticado publica un producto sin adjuntar imagen
        // When: envía el formulario de publicación sin el campo imagen
        $this->actingAs($this->distribuidor)->post(route('products.store'), $this->payload());

        // Then: el producto se crea igualmente y su ruta de imagen queda nula
        $this->assertDatabaseHas('productos_mayoristas', ['nombre' => 'Aceite Primor 1L x 12']);
        $this->assertNull(Product::sole()->imagen_url);
    }

    /**
     * Al editar con una imagen nueva, la anterior se elimina del disco.
     */

    // php artisan test --filter=test_la_edicion_reemplaza_la_imagen_y_elimina_la_anterior
    public function test_la_edicion_reemplaza_la_imagen_y_elimina_la_anterior(): void
    {
        // Given: un distribuidor autenticado que ya publicó un producto con una imagen original
        $this->actingAs($this->distribuidor)->post(
            route('products.store'),
            $this->payload(['imagen' => UploadedFile::fake()->image('original.png', 200, 200)])
        );

        $producto = Product::sole();
        $rutaAnterior = $producto->imagen_url;

        // When: edita el producto adjuntando una imagen nueva
        $this->actingAs($this->distribuidor)->put(
            route('products.update', $producto->id),
            $this->payload([
                'nombre' => 'Aceite Primor 1L x 12 (actualizado)',
                'stock_disponible' => 15,
                'imagen' => UploadedFile::fake()->image('nueva.jpg', 250, 250),
            ])
        );

        $producto->refresh();

        // Then: la ruta cambia, la imagen anterior se elimina del disco y la nueva existe
        $this->assertNotSame($rutaAnterior, $producto->imagen_url);
        Storage::disk('public')->assertMissing($rutaAnterior);
        Storage::disk('public')->assertExists($producto->imagen_url);
    }

    /**
     * El catálogo público muestra la URL pública de la imagen almacenada.
     */

    // php artisan test --filter=test_el_catalogo_publico_expone_la_url_de_la_imagen
    public function test_el_catalogo_publico_expone_la_url_de_la_imagen(): void
    {
        // Given: un distribuidor autenticado publica un producto con una imagen visible
        $this->actingAs($this->distribuidor)->post(
            route('products.store'),
            $this->payload(['imagen' => UploadedFile::fake()->image('visible.png', 200, 200)])
        );

        $ruta = Product::sole()->imagen_url;

        // When: consulta el catálogo público de productos
        // Then: la respuesta es exitosa y muestra la URL pública de la imagen almacenada
        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($ruta), escape: false);
    }
}
