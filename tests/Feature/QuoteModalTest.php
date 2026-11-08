<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ficha CI-TEST-12: escenario "Redirección inteligente al intentar cotizar"
 * de HU-03. El visitante anónimo presiona "Cotizar Pedido" y el sistema debe
 * desplegar un modal informativo que solicite el inicio de sesión y conduzca
 * al login conservando la URL previa.
 */
class QuoteModalTest extends TestCase
{
    use RefreshDatabase;

    private Product $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $distribuidor = User::create([
            'ruc_empresa' => '20601234567',
            'razon_social' => 'Distribuidora Andina S.A.C.',
            'email_contacto' => 'ventas@andina.pe',
            'telefono_whatsapp' => '+51 987654321',
            'rol' => 'distribuidor',
            'password' => bcrypt('password'),
        ]);

        $this->producto = Product::create([
            'distribuidor_id' => $distribuidor->id,
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

    /**
     * El detalle del producto expone el disparador del modal.
     */

    // php artisan test --filter=test_el_detalle_ofrece_el_disparador_del_modal_al_visitante
    public function test_el_detalle_ofrece_el_disparador_del_modal_al_visitante(): void
    {
        // Given: un producto publicado y un visitante anónimo
        // When: abre la página de detalle del producto
        // Then: ve el disparador del modal y el botón "Cotizar pedido"
        $this->get(route('products.show', $this->producto->id))
            ->assertOk()
            ->assertSee('data-modal-abrir="authModal"', escape: false)
            ->assertSee('Cotizar pedido', escape: false);
    }

    /**
     * El modal informa, ofrece ambas salidas y no se auto-redirige: el
     * escenario dice "modal informativo", no "descarte de la página".
     */

    // php artisan test --filter=test_el_modal_es_informativo_y_permite_continuar_explorando
    public function test_el_modal_es_informativo_y_permite_continuar_explorando(): void
    {
        // Given: un visitante anónimo en el detalle de un producto publicado
        // When: abre la página de detalle del producto
        // Then: ve el modal informativo oculto con la opción de seguir explorando
        $this->get(route('products.show', $this->producto->id))
            ->assertOk()
            ->assertSee('id="authModal"', escape: false)
            ->assertSee('Inicia sesión para cotizar', escape: false)
            ->assertSee('Seguir explorando', escape: false)
            // El overlay nace oculto: nada se redirige hasta que el usuario decide.
            ->assertSee('id="authModal" hidden', escape: false);
    }

    /**
     * El modal conduce al login conservando la URL del producto que se miraba.
     */

    // php artisan test --filter=test_el_modal_enlaza_al_login_con_el_return_url_del_producto
    public function test_el_modal_enlaza_al_login_con_el_return_url_del_producto(): void
    {
        // Given: un producto publicado cuyo detalle servirá de destino
        $destino = route('products.show', $this->producto->id);

        // When: el visitante abre el detalle del producto
        // Then: el modal enlaza al login y al registro conservando esa returnUrl
        $this->get(route('products.show', $this->producto->id))
            ->assertOk()
            ->assertSee(route('auth.login', ['returnUrl' => $destino]), escape: false)
            ->assertSee(route('auth.register', ['returnUrl' => $destino]), escape: false);
    }

    /**
     * En la cuadrícula cada tarjeta declara su propio destino, de modo que el
     * modal no devuelve al visitante a un producto distinto del que pulsó.
     */

    // php artisan test --filter=test_la_cuadricula_declara_el_return_url_de_cada_tarjeta
    public function test_la_cuadricula_declara_el_return_url_de_cada_tarjeta(): void
    {
        // Given: un visitante anónimo navegando el catálogo
        // When: abre la cuadrícula del catálogo
        // Then: la tarjeta del producto declara su return-url y el catálogo incluye el modal
        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('data-return-url="'.route('products.show', $this->producto->id).'"', escape: false)
            ->assertSee('id="authModal"', escape: false);
    }

    /**
     * El modal no se renderiza para quien ya tiene sesión: el bodeguero ve el
     * acceso directo a cotizar y el distribuidor no debe ser interrumpido.
     */

    // php artisan test --filter=test_el_bodeguero_autenticado_no_recibe_el_modal
    public function test_el_bodeguero_autenticado_no_recibe_el_modal(): void
    {
        // Given: un usuario autenticado con rol bodega
        $bodega = User::create([
            'ruc_empresa' => '20607654321',
            'razon_social' => 'Bodega El Comercio E.I.R.L.',
            'email_contacto' => 'compras@comercio.pe',
            'telefono_whatsapp' => '+51 912345678',
            'rol' => 'bodega',
            'password' => bcrypt('password'),
        ]);

        // When: abre el detalle de un producto con sesión iniciada
        // Then: no ve el modal y se muestra el enlace directo a cotizar
        $this->actingAs($bodega)
            ->get(route('products.show', $this->producto->id))
            ->assertOk()
            ->assertDontSee('id="authModal"', escape: false)
            ->assertSee(route('quotes.create', ['producto' => $this->producto->id]), escape: false);
    }

    /**
     * El distribuidor autenticado tampoco: para él el catálogo es suyo.
     */

    // php artisan test --filter=test_el_distribuidor_autenticado_no_recibe_el_modal
    public function test_el_distribuidor_autenticado_no_recibe_el_modal(): void
    {
        // Given: el distribuidor dueño del producto, autenticado
        // When: abre el detalle de su propio producto
        // Then: no se le muestra el modal de autenticación
        $this->actingAs($this->producto->distribuidor)
            ->get(route('products.show', $this->producto->id))
            ->assertOk()
            ->assertDontSee('id="authModal"', escape: false);
    }

    /**
     * El modal no se renderiza en el catálogo para un visitante ya autenticado.
     */

    // php artisan test --filter=test_el_catalogo_no_incluye_el_modal_para_usuarios_con_sesion
    public function test_el_catalogo_no_incluye_el_modal_para_usuarios_con_sesion(): void
    {
        // Given: un usuario autenticado con rol bodega
        $bodega = User::create([
            'ruc_empresa' => '20607654322',
            'razon_social' => 'Bodega La Esquina E.I.R.L.',
            'email_contacto' => 'compras@esquina.pe',
            'telefono_whatsapp' => '+51 912345679',
            'rol' => 'bodega',
            'password' => bcrypt('password'),
        ]);

        // When: abre el catálogo con sesión iniciada
        // Then: la cuadrícula no incluye el modal de autenticación
        $this->actingAs($bodega)
            ->get(route('products.index'))
            ->assertOk()
            ->assertDontSee('id="authModal"', escape: false);
    }

    /**
     * El returnUrl del modal sigue siendo revalidado en el servidor: un
     * attacker puede alterar el parámetro a mano aunque el HTML sea correcto.
     */

    // php artisan test --filter=test_el_return_url_del_modal_sigue_saneado_en_el_servidor
    public function test_el_return_url_del_modal_sigue_saneado_en_el_servidor(): void
    {
        // Given: una returnUrl alterada a mano apuntando a un sitio externo malicioso
        // When: el visitante abre el login con esa returnUrl
        // Then: la página responde correctamente sin exponer el dominio externo
        $this->get(route('auth.login', ['returnUrl' => 'https://sitio-malicioso.example/robo']))
            ->assertOk()
            ->assertDontSee('sitio-malicioso.example', escape: false);
    }

    /**
     * Recorrido completo del escenario BDD: el anónimo pulsa, el modal ofrece
     * el login y al autenticar vuelve al producto de origen.
     */

    // php artisan test --filter=test_recorrido_completo_del_visitante_anonico_hasta_la_cotizacion
    public function test_recorrido_completo_del_visitante_anonico_hasta_la_cotizacion(): void
    {
        // Given: un visitante anónimo y el producto publicado en el catálogo
        $destino = route('products.show', $this->producto->id);

        // 1. El visitante llega al detalle sin credenciales.
        // When: abre el detalle del producto y pulsa "Cotizar pedido"
        $this->get($destino)
            ->assertOk()
            ->assertSee('data-modal-abrir="authModal"', escape: false);

        // 2. El modal ofrece el acceso conservando el destino.
        $this->assertStringContainsString(
            'returnUrl='.urlencode($destino),
            (string) $this->get(route('products.show', $this->producto->id))
                ->assertOk()
                ->getContent()
        );

        // 3. Tras autenticarse, la sesión devuelve al producto de origen.
        $bodega = User::create([
            'ruc_empresa' => '20607654323',
            'razon_social' => 'Bodega El Progreso E.I.R.L.',
            'email_contacto' => 'compras@progreso.pe',
            'telefono_whatsapp' => '+51 912345670',
            'rol' => 'bodega',
            'password' => bcrypt('password'),
        ]);

        // When: abre el login con la returnUrl y envía sus credenciales
        $pagina = $this->get(route('auth.login', ['returnUrl' => $destino]));
        $token = $this->extractCsrfToken($pagina->getContent());

        // Then: queda autenticado y redirigido al producto de origen
        $this->post(route('auth.login.post'), [
            '_token' => $token,
            'email' => $bodega->email_contacto,
            'password' => 'password',
        ])->assertRedirect($destino);
    }

    /**
     * Extrae el token CSRF del formulario de login renderizado.
     */
    private function extractCsrfToken(string $html): string
    {
        preg_match('/name="_token"\s+value="([^"]+)"/', $html, $coincidencias);

        return $coincidencias[1] ?? '';
    }
}
