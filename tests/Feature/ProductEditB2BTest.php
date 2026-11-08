<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\ProductUpdated;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductImageStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class ProductEditB2BTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Http::fake();
    }

    public function test_distribuidor_puede_editar_su_propio_producto_con_fakes_y_spies(): void
    {
        // Given: un distribuidor es propietario de un producto con imagen existente.
        $distribuidor = User::factory()->empresario()->create();
        $imagenAnterior = 'productos/imagen-anterior.png';
        Storage::disk('public')->put($imagenAnterior, 'imagen anterior');
        $producto = Product::factory()->create([
            'distribuidor_id' => $distribuidor->id,
            'imagen_url' => $imagenAnterior,
        ]);
        $imagenNueva = UploadedFile::fake()->image('producto-nuevo.png');

        Event::spy();

        // When: actualiza los datos del producto y adjunta una imagen nueva.
        $response = $this->actingAs($distribuidor)->put(
            route('products.update', $producto),
            $this->payload([
                'nombre' => 'Producto actualizado',
                'imagen' => $imagenNueva,
            ])
        );

        // Then: responde correctamente, persiste los cambios y reemplaza la imagen.
        $response->assertRedirect(route('products.show', $producto));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('productos_mayoristas', [
            'id' => $producto->id,
            'distribuidor_id' => $distribuidor->id,
            'nombre' => 'Producto actualizado',
            'precio_bulto' => 125.50,
            'precio_unitario_sugerido' => 10.46,
            'moq_cantidad_minima' => 4,
        ]);

        $producto->refresh();
        $this->assertNotSame($imagenAnterior, $producto->imagen_url);
        $this->assertStringStartsWith('productos/', $producto->imagen_url);
        $this->assertStringEndsWith('.png', $producto->imagen_url);
        Storage::disk('public')->assertMissing($imagenAnterior);
        Storage::disk('public')->assertExists($producto->imagen_url);

        Event::shouldHaveReceived('dispatch')
            ->withArgs(fn (mixed $event): bool => $event instanceof ProductUpdated
                && $event->product->is($producto))
            ->once();
    }

    public function test_bloquea_edicion_de_producto_ajeno_con_http_403_rbac(): void
    {
        // Given: un distribuidor autenticado intenta modificar el producto de otro.
        $distribuidorA = User::factory()->empresario()->create();
        $distribuidorB = User::factory()->empresario()->create();
        $productoAjeno = Product::factory()->create([
            'distribuidor_id' => $distribuidorB->id,
        ]);
        $nombreOriginal = $productoAjeno->nombre;
        $precioOriginal = $productoAjeno->precio_bulto;

        $this->mock(ProductImageStorage::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('store');
            $mock->shouldNotReceive('eliminar');
        });

        // When: envía una actualización válida del producto ajeno.
        $response = $this->actingAs($distribuidorA)->put(
            route('products.update', $productoAjeno),
            $this->payload(['nombre' => 'Intento de modificación'])
        );

        // Then: recibe 403 y la fila conserva sus datos originales.
        $response->assertForbidden();
        $this->assertDatabaseHas('productos_mayoristas', [
            'id' => $productoAjeno->id,
            'distribuidor_id' => $distribuidorB->id,
            'nombre' => $nombreOriginal,
            'precio_bulto' => $precioOriginal,
        ]);
    }

    public function test_solo_muestra_la_accion_de_edicion_en_productos_propios(): void
    {
        // Given: un distribuidor tiene un producto propio y puede ver uno ajeno.
        $distribuidor = User::factory()->empresario()->create();
        $otroDistribuidor = User::factory()->empresario()->create();
        $productoPropio = Product::factory()->create([
            'distribuidor_id' => $distribuidor->id,
        ]);
        $productoAjeno = Product::factory()->create([
            'distribuidor_id' => $otroDistribuidor->id,
        ]);

        // When: consulta el catálogo.
        $response = $this->actingAs($distribuidor)->get(route('products.index'));

        // Then: solo recibe el enlace de edición de su propio producto.
        $response->assertOk()
            ->assertSee(route('products.edit', $productoPropio), escape: false)
            ->assertDontSee(route('products.edit', $productoAjeno), escape: false);

        // When: abre el detalle de cada producto.
        $this->actingAs($distribuidor)->get(route('products.show', $productoPropio))
            ->assertOk()
            ->assertSee('Editar producto', escape: false)
            ->assertSee(route('products.edit', $productoPropio), escape: false);

        // Then: el producto ajeno no ofrece una acción de administración.
        $this->actingAs($distribuidor)->get(route('products.show', $productoAjeno))
            ->assertOk()
            ->assertDontSee('Editar producto', escape: false)
            ->assertDontSee(route('products.edit', $productoAjeno), escape: false);
    }

    public function test_rechaza_actualizacion_con_precio_o_moq_invalidos(): void
    {
        // Given: un distribuidor propietario y un producto que no debe modificarse.
        $distribuidor = User::factory()->empresario()->create();
        $producto = Product::factory()->create([
            'distribuidor_id' => $distribuidor->id,
        ]);
        $nombreOriginal = $producto->nombre;
        $precioOriginal = $producto->precio_bulto;

        // When: el precio no es positivo, excede dos decimales o el MOQ no es positivo.
        foreach ([
            ['precio_bulto' => 0],
            ['precio_bulto' => 12.345],
            ['moq_cantidad_minima' => 0],
        ] as $invalidData) {
            $response = $this->actingAs($distribuidor)->putJson(
                route('products.update', $producto),
                $this->payload($invalidData)
            );

            // Then: cada solicitud inválida responde 422 y reporta el campo correspondiente.
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(array_keys($invalidData));
        }

        $this->assertDatabaseHas('productos_mayoristas', [
            'id' => $producto->id,
            'nombre' => $nombreOriginal,
            'precio_bulto' => $precioOriginal,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Producto para edición',
            'categoria' => 'Abarrotes',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 12,
            'precio_bulto' => 125.50,
            'moq_cantidad_minima' => 4,
            'stock_disponible' => 100,
            'descripcion' => 'Descripción actualizada',
        ], $overrides);
    }
}
