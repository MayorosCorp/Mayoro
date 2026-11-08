<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RN-01: el catalogo y la cotizacion son de acceso libre. El visitante no
 * encuentra un muro de login ni un RUC obligatorio para generar su enlace.
 */
class PublicQuoteAccessTest extends TestCase
{
    use RefreshDatabase;

    private Product $producto;

    private User $empresario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresario = User::factory()->empresario()->create([
            'razon_social' => 'Distribuidora Andina S.A.C.',
            'telefono_whatsapp' => '+51987654321',
        ]);

        $this->producto = Product::create([
            'distribuidor_id' => $this->empresario->id,
            'nombre' => 'Atun Conservas 170g x 24',
            'categoria' => 'Enlatados',
            'presentacion' => 'Caja',
            'unidades_por_bulto' => 24,
            'precio_bulto' => 96.00,
            'precio_unitario_sugerido' => 4.00,
            'moq_cantidad_minima' => 4,
            'stock_disponible' => 40,
            'is_active' => true,
        ]);
    }

    // php artisan test --filter=test_el_detalle_ofrece_cotizar_directo_al_visitante_sin_modal
    public function test_el_detalle_ofrece_cotizar_directo_al_visitante_sin_modal(): void
    {
        // Given: un producto publicado y un visitante anonimo
        // When: abre el detalle del producto
        // Then: ve el enlace directo al formulario publico y ningun muro de login
        $this->get(route('products.show', $this->producto->id))
            ->assertOk()
            ->assertSee('data-testid="producto-cotizar"', escape: false)
            ->assertSee(route('quotes.create', ['producto' => $this->producto->id]), escape: false)
            ->assertDontSee('id="authModal"', escape: false);
    }

    // php artisan test --filter=test_la_cuadricula_ofrece_cotizar_directo_al_visitante
    public function test_la_cuadricula_ofrece_cotizar_directo_al_visitante(): void
    {
        // Given: un visitante anonimo navegando el catalogo
        // When: abre la cuadricula
        // Then: cada tarjeta enlaza al formulario publico de cotizacion
        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('data-testid="producto-cotizar"', escape: false)
            ->assertSee(route('quotes.create', ['producto' => $this->producto->id]), escape: false)
            ->assertDontSee('id="authModal"', escape: false);
    }

    // php artisan test --filter=test_el_formulario_publico_explica_que_no_exige_ruc
    public function test_el_formulario_publico_explica_que_no_exige_ruc(): void
    {
        // Given: un visitante sin cuenta
        // When: abre el formulario de cotizacion
        // Then: se le explica que no necesita RUC ni cuenta
        $this->get(route('quotes.create'))
            ->assertOk()
            ->assertSee('no pedimos RUC ni cuenta');
    }

    // php artisan test --filter=test_persona_natural_autenticada_cotiza_directo
    public function test_persona_natural_autenticada_cotiza_directo(): void
    {
        // Given: una Persona natural autenticada
        $persona = User::factory()->personaNatural()->create();

        // When: abre el detalle del producto
        // Then: mantiene el enlace directo a cotizar
        $this->actingAs($persona)
            ->get(route('products.show', $this->producto->id))
            ->assertOk()
            ->assertSee(route('quotes.create', ['producto' => $this->producto->id]), escape: false);
    }

    // php artisan test --filter=test_empresario_no_genera_cotizaciones_con_su_propio_rol
    public function test_empresario_no_genera_cotizaciones_con_su_propio_rol(): void
    {
        // Given: un Empresario autenticado mirando el producto de otro Empresario
        $otro = User::factory()->empresario()->create();

        // When: abre el detalle
        // Then: no se le ofrece cotizar; se le invita a volver al catalogo
        $this->actingAs($otro)
            ->get(route('products.show', $this->producto->id))
            ->assertOk()
            ->assertDontSee('data-testid="producto-cotizar"', escape: false)
            ->assertSee('Producto de otro Empresario');
    }

    // php artisan test --filter=test_el_registro_pide_nombre_y_no_ruc
    public function test_el_registro_pide_nombre_y_no_ruc(): void
    {
        // Given: un visitante que decide crear cuenta
        // When: abre el registro
        // Then: se le pide nombre completo y no se le exige RUC
        $this->get(route('auth.register'))
            ->assertOk()
            ->assertSee('Nombre completo')
            ->assertSee('El RUC solo se pide al convertirte en Empresario')
            ->assertDontSee('name="ruc_empresa"', escape: false)
            ->assertDontSee('name="rol"', escape: false);
    }
}
