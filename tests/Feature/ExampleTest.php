<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */

    // php artisan test --filter=test_the_application_returns_a_successful_response
    public function test_the_application_returns_a_successful_response(): void
    {
        // Given: la aplicación está disponible y atiende peticiones HTTP
        // When: se solicita la ruta raíz
        $response = $this->get('/');

        // Then: la respuesta es exitosa (HTTP 200)
        $response->assertStatus(200);
    }
}
