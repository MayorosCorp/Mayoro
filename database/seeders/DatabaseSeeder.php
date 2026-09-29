<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * Genera comercios B2B (HU-01) y productos mayoristas (HU-02) alineados
     * al esquema de las tablas usuarios_b2b y productos_mayoristas.
     */
    public function run(): void
    {
        $distribuidor = User::query()->updateOrCreate(
            ['ruc_empresa' => '20123456789'],
            [
                'razon_social' => 'Distribuidora Andina S.A.C.',
                'email_contacto' => 'ventas@distribuidora-andina.pe',
                'telefono_whatsapp' => '51987654321',
                'direccion' => 'Av. Los Álamos 1450, Lima',
                'password' => Hash::make('password'),
                'rol' => 'distribuidor',
                'is_premium' => true,
            ]
        );

        $bodega = User::query()->updateOrCreate(
            ['ruc_empresa' => '20987654321'],
            [
                'razon_social' => 'Bodega El Comercio E.I.R.L.',
                'email_contacto' => 'compras@bodegaelcomercio.pe',
                'telefono_whatsapp' => '51911223344',
                'direccion' => 'Jr. Garcilaso 210, Cusco',
                'password' => Hash::make('password'),
                'rol' => 'bodega',
                'is_premium' => false,
            ]
        );

        $productos = [
            [
                'nombre' => 'Cerveza Cristal 650 ml',
                'descripcion' => 'Cerveza pilsener en botella de vidrio. Lote con fecha de vencimiento mayor a 6 meses.',
                'categoria' => 'Bebidas',
                'presentacion' => 'Caja',
                'unidades_por_bulto' => 12,
                'precio_bulto' => 78.00,
                'moq_cantidad_minima' => 20,
            ],
            [
                'nombre' => 'Gaseosa Cola 1.5 L',
                'descripcion' => 'Gaseosa azucarada en botella PET. Embalaje retornable disponible.',
                'categoria' => 'Bebidas',
                'presentacion' => 'Paquete',
                'unidades_por_bulto' => 6,
                'precio_bulto' => 24.50,
                'moq_cantidad_minima' => 30,
            ],
            [
                'nombre' => 'Atún en Lata 425 g',
                'descripcion' => 'Atún en aceite vegetal. Caja x 48 latas, ideal para minimarket.',
                'categoria' => 'Abarrotes',
                'presentacion' => 'Caja',
                'unidades_por_bulto' => 48,
                'precio_bulto' => 156.00,
                'moq_cantidad_minima' => 10,
            ],
            [
                'nombre' => 'Arroz Pilado Superior 5 kg',
                'descripcion' => 'Arroz pilado de grano largo. Fardo con 4 bolsas de 5 kg.',
                'categoria' => 'Abarrotes',
                'presentacion' => 'Fardo',
                'unidades_por_bulto' => 4,
                'precio_bulto' => 92.00,
                'moq_cantidad_minima' => 15,
            ],
            [
                'nombre' => 'Azúcar Rubia 1 kg',
                'descripcion' => 'Azúcar rubia refinada. Saco con 20 bolsas de 1 kg.',
                'categoria' => 'Abarrotes',
                'presentacion' => 'Saco',
                'unidades_por_bulto' => 20,
                'precio_bulto' => 68.00,
                'moq_cantidad_minima' => 12,
            ],
            [
                'nombre' => 'Leche Evaporada 400 ml',
                'descripcion' => 'Leche evaporada entera. Caja x 24 unidades.',
                'categoria' => 'Lácteos',
                'presentacion' => 'Caja',
                'unidades_por_bulto' => 24,
                'precio_bulto' => 84.00,
                'moq_cantidad_minima' => 18,
            ],
            [
                'nombre' => 'Detergente 3 kg',
                'descripcion' => 'Detergente en polvo para ropa. Fardo con 4 unidades.',
                'categoria' => 'Limpieza',
                'presentacion' => 'Fardo',
                'unidades_por_bulto' => 4,
                'precio_bulto' => 56.00,
                'moq_cantidad_minima' => 25,
            ],
            [
                'nombre' => 'Papel Higiénico Doble Hoja',
                'descripcion' => 'Papel higiénico doble hoja, 12 rollos por paquete.',
                'categoria' => 'Limpieza',
                'presentacion' => 'Paquete',
                'unidades_por_bulto' => 12,
                'precio_bulto' => 39.90,
                'moq_cantidad_minima' => 20,
            ],
        ];

        foreach ($productos as $producto) {
            $unidades = $producto['unidades_por_bulto'];

            Product::query()->updateOrCreate(
                [
                    'distribuidor_id' => $distribuidor->id,
                    'nombre' => $producto['nombre'],
                ],
                [
                    'descripcion' => $producto['descripcion'],
                    'categoria' => $producto['categoria'],
                    'presentacion' => $producto['presentacion'],
                    'unidades_por_bulto' => $unidades,
                    'precio_bulto' => $producto['precio_bulto'],
                    'moq_cantidad_minima' => $producto['moq_cantidad_minima'],
                    'precio_unitario_sugerido' => round($producto['precio_bulto'] / $unidades, 2),
                    'imagen_url' => null,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Comercios B2B: 2 (1 distribuidor, 1 bodega).');
        $this->command?->info('Productos mayoristas: '.count($productos));
        $this->command?->info('Acceso distribuidor: ventas@distribuidora-andina.pe / password');
        $this->command?->info('Acceso bodega: compras@bodegaelcomercio.pe / password');
    }
}
