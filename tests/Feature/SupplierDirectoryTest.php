<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private User $distribuidor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->distribuidor = User::factory()->empresario()->create([
            'razon_social' => 'Distribuidora Andina S.A.C.',
            'ruc_empresa' => '20123456789',
        ]);
    }

    /**
     * HU-03: El directorio lista los comercios distribuidores reales, que antes
     * era un módulo placeholder sin datos.
     */

    // php artisan test --filter=test_el_directorio_muestra_los_distribuidores_registrados
    public function test_el_directorio_muestra_los_distribuidores_registrados(): void
    {
        // Given: al menos un distribuidor registrado en el sistema
        // When: se visita el directorio público de proveedores
        $response = $this->get(route('suppliers.index'));

        // Then: se listan su razón social y su RUC
        $response->assertOk();
        $response->assertSee('Distribuidora Andina S.A.C.');
        $response->assertSee('20123456789');
    }

    /**
     * El directorio no debe exponer los comercios con rol bodega.
     */

    // php artisan test --filter=test_el_directorio_excluye_los_comercios_con_rol_bodega
    public function test_el_directorio_excluye_los_comercios_con_rol_bodega(): void
    {
        // Given: un comercio con rol bodega que no debe figurar en el directorio
        User::factory()->personaNatural()->create(['razon_social' => 'Bodega No Listada E.I.R.L.']);

        // When: se visita el directorio de proveedores
        $response = $this->get(route('suppliers.index'));

        // Then: el comercio con rol bodega no se muestra
        $response->assertOk();
        $response->assertDontSee('Bodega No Listada E.I.R.L.');
    }

    /**
     * El buscador filtra por razón social o RUC del distribuidor.
     */

    // php artisan test --filter=test_el_buscador_filtra_por_razon_social_y_ruc
    public function test_el_buscador_filtra_por_razon_social_y_ruc(): void
    {
        // Given: un segundo distribuidor con razón social y RUC distintos al creado en setUp
        User::factory()->empresario()->create([
            'razon_social' => 'Comercial del Norte E.I.R.L.',
            'ruc_empresa' => '20765432109',
        ]);

        // When: se busca en el directorio por razón social y luego por RUC
        // Then: solo el distribuidor que coincide con el criterio de búsqueda se muestra
        $this->get(route('suppliers.index', ['q' => 'Norte']))
            ->assertOk()
            ->assertSee('Comercial del Norte E.I.R.L.')
            ->assertDontSee('Distribuidora Andina S.A.C.');

        $this->get(route('suppliers.index', ['q' => '20765432109']))
            ->assertOk()
            ->assertSee('Comercial del Norte E.I.R.L.');
    }

    /**
     * La ficha del distribuidor muestra su catálogo mayorista vigente.
     */

    // php artisan test --filter=test_la_ficha_del_distribuidor_lista_su_catalogo_activo
    public function test_la_ficha_del_distribuidor_lista_su_catalogo_activo(): void
    {
        // Given: un distribuidor con un producto vigente y otro pausado
        Product::factory()->create([
            'distribuidor_id' => $this->distribuidor->id,
            'nombre' => 'Atún en Lata 425 g',
        ]);

        Product::factory()->pausado()->create([
            'distribuidor_id' => $this->distribuidor->id,
            'nombre' => 'Producto Retirado',
        ]);

        // When: se visita la ficha pública del distribuidor
        $response = $this->get(route('suppliers.show', $this->distribuidor->id));

        // Then: solo el producto vigente se lista en su catálogo
        $response->assertOk();
        $response->assertSee('Atún en Lata 425 g');
        $response->assertDontSee('Producto Retirado');
    }

    /**
     * Un id de bodega no puede invocarse como ficha de distribuidor.
     */

    // php artisan test --filter=test_un_id_de_bodega_no_resuelve_como_ficha_de_distribuidor
    public function test_un_id_de_bodega_no_resuelve_como_ficha_de_distribuidor(): void
    {
        // Given: un comercio con rol bodega
        $bodega = User::factory()->personaNatural()->create();

        // When: se solicita su ficha usando su id como si fuera distribuidor
        // Then: la solicitud responde con HTTP 404
        $this->get(route('suppliers.show', $bodega->id))->assertNotFound();
    }

    /**
     * La ficha es accesible sin autenticación, es parte de la captación pública.
     */

    // php artisan test --filter=test_la_ficha_del_distribuidor_es_publica
    public function test_la_ficha_del_distribuidor_es_publica(): void
    {
        // Given: un distribuidor con un producto publicado
        Product::factory()->create([
            'distribuidor_id' => $this->distribuidor->id,
            'nombre' => 'Producto Visible',
        ]);

        // When: un visitante sin autenticar visita su ficha pública
        // Then: ve la ficha, su catálogo y la llamada a la acción para cotizar
        $this->get(route('suppliers.show', $this->distribuidor->id))
            ->assertOk()
            ->assertSee('Distribuidora Andina S.A.C.')
            ->assertSee('Producto Visible')
            ->assertSee('Cotizar pedido');
    }
}
