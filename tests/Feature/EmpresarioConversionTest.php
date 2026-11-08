<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RN-02: la Persona natural se convierte en Empresario solo despues de validar
 * un RUC peruano con el digito verificador del Modulo 11.
 */
class EmpresarioConversionTest extends TestCase
{
    use RefreshDatabase;

    // php artisan test --filter=test_persona_natural_se_convierte_en_empresario_con_ruc_valido
    public function test_persona_natural_se_convierte_en_empresario_con_ruc_valido(): void
    {
        // Given: una Persona natural autenticada
        $persona = User::factory()->personaNatural()->create([
            'telefono_whatsapp' => '+51987654321',
        ]);

        // When: completa el formulario con un RUC que supera el Modulo 11
        $response = $this->actingAs($persona)->post(route('profile.empresario.convertir'), [
            'ruc_empresa' => '20123456786',
            'razon_social' => 'Distribuidora Los Andes S.A.C.',
            'rubro' => 'Abarrotes y bebidas',
            'telefono_whatsapp' => '987654321',
            'direccion' => 'Av. Los Alamos 1450, Lima',
        ]);

        // Then: la cuenta pasa a Empresario y conserva los datos del negocio
        $response->assertRedirect(route('dashboard.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('usuarios_b2b', [
            'id' => $persona->id,
            'ruc_empresa' => '20123456786',
            'razon_social' => 'Distribuidora Los Andes S.A.C.',
            'rubro' => 'Abarrotes y bebidas',
            'telefono_whatsapp' => '+51987654321',
            'rol' => User::ROL_EMPRESARIO,
        ]);
    }

    // php artisan test --filter=test_conversion_rechaza_ruc_con_digito_verificador_invalido
    public function test_conversion_rechaza_ruc_con_digito_verificador_invalido(): void
    {
        // Given: una Persona natural que intenta usar un RUC con check digit errado
        $persona = User::factory()->personaNatural()->create();

        // When: envia el formulario
        $response = $this->actingAs($persona)->post(route('profile.empresario.convertir'), [
            'ruc_empresa' => '20123456788',
            'razon_social' => 'Distribuidora Fantasma S.A.C.',
            'rubro' => 'Abarrotes',
            'telefono_whatsapp' => '987654321',
        ]);

        // Then: el RUC se rechaza y la cuenta sigue siendo Persona natural
        $response->assertSessionHasErrors('ruc_empresa');
        $this->assertDatabaseHas('usuarios_b2b', [
            'id' => $persona->id,
            'ruc_empresa' => null,
            'rol' => User::ROL_PERSONA_NATURAL,
        ]);
    }

    // php artisan test --filter=test_conversion_rechaza_ruc_con_prefijo_no_permitido
    public function test_conversion_rechaza_ruc_con_prefijo_no_permitido(): void
    {
        // Given: una Persona natural que envia un RUC con prefijo ajeno a 10 o 20
        $persona = User::factory()->personaNatural()->create();

        // When: envia el formulario
        $response = $this->actingAs($persona)->post(route('profile.empresario.convertir'), [
            'ruc_empresa' => '15123456787',
            'razon_social' => 'Negocio No Permitido',
            'rubro' => 'Abarrotes',
            'telefono_whatsapp' => '987654321',
        ]);

        // Then: se rechaza por el prefijo y no cambia el rol
        $response->assertSessionHasErrors('ruc_empresa');
        $this->assertDatabaseHas('usuarios_b2b', [
            'id' => $persona->id,
            'rol' => User::ROL_PERSONA_NATURAL,
        ]);
    }

    // php artisan test --filter=test_conversion_rechaza_ruc_ya_registrado_en_otra_cuenta
    public function test_conversion_rechaza_ruc_ya_registrado_en_otra_cuenta(): void
    {
        // Given: un RUC ya usado por otro Empresario
        User::factory()->empresario()->create([
            'ruc_empresa' => '20987654326',
            'email_contacto' => 'ventas@sur.pe',
        ]);

        $persona = User::factory()->personaNatural()->create();

        // When: otra Persona natural intenta convertirse con ese RUC
        $response = $this->actingAs($persona)->post(route('profile.empresario.convertir'), [
            'ruc_empresa' => '20987654326',
            'razon_social' => 'Distribuidora del Sur S.A.C.',
            'rubro' => 'Bebidas',
            'telefono_whatsapp' => '987654321',
        ]);

        // Then: la unicidad del RUC se valida en el servidor
        $response->assertSessionHasErrors('ruc_empresa');
        $this->assertDatabaseHas('usuarios_b2b', [
            'id' => $persona->id,
            'rol' => User::ROL_PERSONA_NATURAL,
        ]);
    }

    // php artisan test --filter=test_empresario_ya_convertido_no_vuelve_a_ver_el_formulario
    public function test_empresario_ya_convertido_no_vuelve_a_ver_el_formulario(): void
    {
        // Given: una cuenta que ya es Empresario
        $empresario = User::factory()->empresario()->create();

        // When: intenta abrir de nuevo la conversion
        // Then: se le devuelve al dashboard sin formulario
        $this->actingAs($empresario)
            ->get(route('profile.empresario'))
            ->assertRedirect(route('dashboard.index'));
    }

    // php artisan test --filter=test_persona_natural_publica_solo_despues_de_convertirse
    public function test_persona_natural_publica_solo_despues_de_convertirse(): void
    {
        // Given: una Persona natural autenticada
        $persona = User::factory()->personaNatural()->create();

        // When: intenta abrir el formulario de publicacion
        // Then: el servidor le responde 403
        $this->actingAs($persona)->get(route('products.create'))->assertForbidden();

        // When: completa la conversion con un RUC valido
        $this->actingAs($persona)->post(route('profile.empresario.convertir'), [
            'ruc_empresa' => '10456789019',
            'razon_social' => 'Bodega San Pedro',
            'rubro' => 'Abarrotes',
            'telefono_whatsapp' => '987654321',
        ]);

        // Then: ya puede abrir el formulario de publicacion
        $this->actingAs($persona->fresh())
            ->get(route('products.create'))
            ->assertOk();
    }
}
