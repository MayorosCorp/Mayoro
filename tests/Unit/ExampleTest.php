<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */

    // php artisan test --filter=test_that_true_is_true
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
