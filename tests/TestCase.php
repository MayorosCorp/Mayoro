<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // El layout carga @vite; las pruebas no necesitan compilar assets ni
        // leer un manifest de build para verificar el HTML.
        $this->withoutVite();
    }
}
