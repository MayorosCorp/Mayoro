<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RN-03: el Empresario validado tiene un micrositio publico y editable.
 * El catalogo se filtra por propiedad en el servidor y la edicion no recibe
 * un id: siempre se opera sobre la cuenta autenticada.
 */
class MicrositeTest extends TestCase
{
    use RefreshDatabase;

    private function empresarioConMicrositio(array $atributos = []): User
    {
        return User::factory()->empresario()->create(array_merge([
            'razon_social' => 'Distribuidora Andina S.A.C.',
            'slug' => 'distribuidora-andina',
            'rubro' => 'Abarrotes y bebidas',
            'descripcion' => 'Distribuimos abarrotes y bebidas para bodegas de Lima.',
            'color_primario' => '#12305c',
            'horario_atencion' => 'Lunes a viernes, 8:00 a 18:00',
            'telefono_whatsapp' => '+51987654321',
        ], $atributos));
    }

    // php artisan test --filter=test_el_micrositio_publico_muestra_el_branding_y_el_catalogo_vigente
    public function test_el_micrositio_publico_muestra_el_branding_y_el_catalogo_vigente(): void
    {
        // Given: un Empresario con branding y un producto activo mas otro pausado
        $empresario = $this->empresarioConMicrositio();

        Product::factory()->create([
            'distribuidor_id' => $empresario->id,
            'nombre' => 'Atún Micrositio 425 g',
        ]);

        Product::factory()->pausado()->create([
            'distribuidor_id' => $empresario->id,
            'nombre' => 'Producto Pausado del Micrositio',
        ]);

        // When: un visitante anonimo abre la vitrina publica
        $response = $this->get(route('microsite.show', 'distribuidora-andina'));

        // Then: ve el branding y solo el catalogo vigente
        $response->assertOk();
        $response->assertSee('Distribuidora Andina S.A.C.');
        $response->assertSee('Distribuimos abarrotes y bebidas para bodegas de Lima.');
        $response->assertSee('Lunes a viernes, 8:00 a 18:00');
        $response->assertSee('Atún Micrositio 425 g');
        $response->assertDontSee('Producto Pausado del Micrositio');
        $response->assertSee('data-testid="micrositio-hero"', escape: false);
        $response->assertSee('data-testid="micrositio-catalogo"', escape: false);
        $response->assertSee('#12305c', escape: false);
    }

    // php artisan test --filter=test_un_slug_inexistente_responde_con_404
    public function test_un_slug_inexistente_responde_con_404(): void
    {
        // Given: ningun Empresario con ese slug
        // When: se solicita la vitrina publica
        // Then: la respuesta es HTTP 404
        $this->get(route('microsite.show', 'empresa-inexistente'))->assertNotFound();
    }

    // php artisan test --filter=test_una_persona_natural_no_expone_micrositio_publico
    public function test_una_persona_natural_no_expone_micrositio_publico(): void
    {
        // Given: una Persona natural con un slug asignado manualmente
        User::factory()->personaNatural()->create([
            'razon_social' => 'Bodega No Empresaria',
            'slug' => 'bodega-no-empresaria',
        ]);

        // When: se intenta abrir su supuesto micrositio
        // Then: el filtro de rol en el servidor responde 404
        $this->get(route('microsite.show', 'bodega-no-empresaria'))->assertNotFound();
    }

    // php artisan test --filter=test_el_buscador_del_micrositio_filtra_solo_su_catalogo
    public function test_el_buscador_del_micrositio_filtra_solo_su_catalogo(): void
    {
        // Given: un Empresario con dos productos activos
        $empresario = $this->empresarioConMicrositio();

        Product::factory()->create([
            'distribuidor_id' => $empresario->id,
            'nombre' => 'Arroz del Micrositio',
        ]);

        Product::factory()->create([
            'distribuidor_id' => $empresario->id,
            'nombre' => 'Detergente del Micrositio',
        ]);

        // When: busca una palabra dentro de su micrositio
        $response = $this->get(route('microsite.show', [
            'slug' => 'distribuidora-andina',
            'q' => 'Arroz',
        ]));

        // Then: solo se lista el producto que coincide
        $response->assertOk();
        $response->assertSee('Arroz del Micrositio');
        $response->assertDontSee('Detergente del Micrositio');
    }

    // php artisan test --filter=test_solo_el_empresario_dueno_accede_al_editor_de_su_micrositio
    public function test_solo_el_empresario_dueno_accede_al_editor_de_su_micrositio(): void
    {
        // Given: una Persona natural, un Empresario dueno y otro Empresario
        $persona = User::factory()->personaNatural()->create();
        $dueno = $this->empresarioConMicrositio();
        $otro = $this->empresarioConMicrositio([
            'razon_social' => 'Comercial del Norte S.A.C.',
            'slug' => 'comercial-del-norte',
        ]);

        // When: cada rol intenta abrir el editor
        // Then: la Persona natural recibe 403
        $this->actingAs($persona)
            ->get(route('microsite.edit'))
            ->assertForbidden();

        // Then: el dueno edita su propia vitrina
        $this->actingAs($dueno)
            ->get(route('microsite.edit'))
            ->assertOk()
            ->assertSee('data-testid="micrositio-form"', escape: false)
            ->assertSee('distribuidora-andina');

        // Then: otro Empresario solo ve la suya, nunca la del dueno
        $this->actingAs($otro)
            ->get(route('microsite.edit'))
            ->assertOk()
            ->assertSee('comercial-del-norte')
            ->assertDontSee('/empresas/distribuidora-andina', escape: false);
    }

    // php artisan test --filter=test_el_empresario_actualiza_su_micrositio_y_cambia_su_url
    public function test_el_empresario_actualiza_su_micrositio_y_cambia_su_url(): void
    {
        // Given: un Empresario autenticado en su micrositio
        $empresario = $this->empresarioConMicrositio();

        // When: guarda un nuevo slug, color, descripcion y horario
        $response = $this->actingAs($empresario)->put(route('microsite.update'), [
            'slug' => 'andina-mayorista',
            'descripcion' => 'Nueva descripcion del micrositio.',
            'color_primario' => '#F59E0B',
            'horario_atencion' => 'Lunes a sabado, 9:00 a 17:00',
        ]);

        // Then: se persiste y la direccion anterior deja de existir
        $response->assertRedirect(route('microsite.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('usuarios_b2b', [
            'id' => $empresario->id,
            'slug' => 'andina-mayorista',
            'descripcion' => 'Nueva descripcion del micrositio.',
            'color_primario' => '#f59e0b',
            'horario_atencion' => 'Lunes a sabado, 9:00 a 17:00',
        ]);

        $this->get(route('microsite.show', 'distribuidora-andina'))->assertNotFound();
        $this->get(route('microsite.show', 'andina-mayorista'))->assertOk();
    }

    // php artisan test --filter=test_el_slug_del_micrositio_no_puede_repetirse
    public function test_el_slug_del_micrositio_no_puede_repetirse(): void
    {
        // Given: un micrositio ya publicado con el slug andina
        $this->empresarioConMicrositio();

        $otro = $this->empresarioConMicrositio([
            'razon_social' => 'Comercial del Norte S.A.C.',
            'slug' => 'comercial-del-norte',
        ]);

        // When: otro Empresario intenta usar el mismo slug
        $response = $this->actingAs($otro)->put(route('microsite.update'), [
            'slug' => 'distribuidora-andina',
            'descripcion' => 'Intento de suplantacion.',
            'color_primario' => '#23497b',
        ]);

        // Then: la validacion de unicidad lo impide y conserva su slug
        $response->assertSessionHasErrors('slug');
        $this->assertDatabaseHas('usuarios_b2b', [
            'id' => $otro->id,
            'slug' => 'comercial-del-norte',
        ]);
    }

    // php artisan test --filter=test_el_color_primario_del_micrositio_debe_ser_hexadecimal
    public function test_el_color_primario_del_micrositio_debe_ser_hexadecimal(): void
    {
        // Given: un Empresario autenticado
        $empresario = $this->empresarioConMicrositio();

        // When: envia un color que no es hexadecimal
        $response = $this->actingAs($empresario)->put(route('microsite.update'), [
            'slug' => 'distribuidora-andina',
            'descripcion' => 'Descripcion valida.',
            'color_primario' => 'azul',
        ]);

        // Then: el color se rechaza y no se modifica el micrositio
        $response->assertSessionHasErrors('color_primario');
        $this->assertDatabaseHas('usuarios_b2b', [
            'id' => $empresario->id,
            'color_primario' => '#12305c',
        ]);
    }

    // php artisan test --filter=test_la_edicion_genera_slug_cuando_el_empresario_no_lo_tiene
    public function test_la_edicion_genera_slug_cuando_el_empresario_no_lo_tiene(): void
    {
        // Given: un Empresario heredado sin slug
        $empresario = User::factory()->empresario()->create([
            'razon_social' => 'Comercial Nueva Ruta S.A.C.',
            'slug' => null,
        ]);

        // When: abre el editor de su micrositio
        $this->actingAs($empresario)
            ->get(route('microsite.edit'))
            ->assertOk();

        // Then: el servidor completa un slug publico unico
        $this->assertNotNull($empresario->fresh()->slug);
        $this->assertStringStartsWith('comercial-nueva-ruta', $empresario->fresh()->slug);
    }
}
