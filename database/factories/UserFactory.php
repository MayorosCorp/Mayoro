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
        $razonSocial = fake()->company().' S.A.C.';

        return [
            'ruc_empresa' => null,
            'razon_social' => $razonSocial,
            'rubro' => fake()->randomElement(['Abarrotes', 'Bebidas', 'Limpieza', 'Lácteos']),
            'email_contacto' => Str::slug($razonSocial, '.').'@'.fake()->domainName(),
            'telefono_whatsapp' => '+51'.fake()->numerify('9########'),
            'direccion' => fake()->address(),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => User::ROL_PERSONA_NATURAL,
            'is_premium' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * RN-02: Empresario validado, el unico rol que publica productos.
     */
    public function empresario(): static
    {
        return $this->state(fn (array $attributes) => [
            'ruc_empresa' => self::rucValido(),
            'rol' => User::ROL_EMPRESARIO,
        ]);
    }

    /**
     * RN-01: Persona natural, sin RUC obligatorio.
     */
    public function personaNatural(): static
    {
        return $this->state(fn (array $attributes) => [
            'ruc_empresa' => null,
            'rol' => User::ROL_PERSONA_NATURAL,
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

    /**
     * Genera un RUC de persona juridica con el digito verificador correcto.
     */
    private static function rucValido(): string
    {
        $base = '20'.fake()->unique()->numerify('########');
        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 10; $i++) {
            $suma += (int) $base[$i] * $pesos[$i];
        }

        $resto = 11 - ($suma % 11);
        $digito = match ($resto) {
            10 => 0,
            11 => 1,
            default => $resto,
        };

        return $base.$digito;
    }
}
