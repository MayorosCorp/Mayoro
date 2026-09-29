<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     * El modelo User apunta a la tabla usuarios_b2b (HU-01), por lo que la
     * factory debe generar ruc_empresa, razon_social, email_contacto,
     * telefono_whatsapp y rol, no name/email.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ruc = (string) fake()->unique()->numerify('20#########');
        $razonSocial = fake()->company().' S.A.C.';

        return [
            'ruc_empresa' => $ruc,
            'razon_social' => $razonSocial,
            'email_contacto' => Str::slug($razonSocial, '.').'@'.fake()->domainName(),
            'telefono_whatsapp' => '51'.fake()->numerify('9########'),
            'direccion' => fake()->address(),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => 'bodega',
            'is_premium' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Comercio con rol distribuidor mayorista.
     */
    public function distribuidor(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'distribuidor',
        ]);
    }

    /**
     * Comercio con rol bodega compradora.
     */
    public function bodega(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'bodega',
        ]);
    }

    /**
     * Indica que el comercio tiene plan premium habilitado.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_premium' => true,
        ]);
    }
}
