<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     * Genera productos mayoristas alineados al esquema de productos_mayoristas
     * (HU-02 y HU-03), incluido el stock que determina el estado "Sin Stock".
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unidades = fake()->numberBetween(1, 24);
        $precioBulto = fake()->randomFloat(2, 15, 500);

        return [
            'distribuidor_id' => User::factory()->distribuidor(),
            'nombre' => fake()->unique()->words(3, true),
            'descripcion' => fake()->sentence(8),
            'categoria' => fake()->randomElement(['Abarrotes', 'Lácteos', 'Bebidas', 'Golosinas', 'Limpieza']),
            'presentacion' => fake()->randomElement(['Caja', 'Saco', 'Fardo', 'Display', 'Pallet']),
            'unidades_por_bulto' => $unidades,
            'precio_bulto' => $precioBulto,
            'precio_unitario_sugerido' => round($precioBulto / $unidades, 2),
            'moq_cantidad_minima' => fake()->numberBetween(1, 30),
            'stock_disponible' => fake()->numberBetween(0, 200),
            'imagen_url' => null,
            'is_active' => true,
        ];
    }

    /**
     * Producto pausado por el distribuidor.
     */
    public function pausado(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Producto agotado, se etiqueta como "Temporalmente sin stock" (HU-03).
     */
    public function agotado(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_disponible' => 0,
        ]);
    }
}
