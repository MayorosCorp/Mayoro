<?php

namespace Tests\Unit;

use App\Services\ProductImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Ficha CI-TEST-16: pruebas unitarias de ProductImageStorage (HU-02).
 *
 * EXISTSIA UNA BRECHA DE COBERTURA. El servicioProductImageStorage solo
 * estaba cubierto por la suite BDD ProductImageUploadTest, que sube la
 * imagen por HTTP, migra la base de datos y escribe en el disco real. Eso
 * demuestra que el flujo completo funciona, pero no ejercita la lógica de
 * generación de nombres ni los caminos de error del servicio, y lo
 * demuestra un caso concreto: antes de estas pruebas, `eliminar()` lanzaba
 * una excepcion no controlada ante una ruta con segmentos "..", y ninguna
 * prueba lo detectaba porque la suite BDD nunca le pasa rutas hostiles.
 *
 * Estas pruebas aisan el disco con Storage::fake y no tocan la base de
 * datos ni el sistema de archivos real. La duplicacion con la suite BDD es
 * intencionada: alli se comprueba el contrato HTTP, aqui la logica.
 */
class ProductImageStorageTest extends TestCase
{
    private ProductImageStorage $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->servicio = new ProductImageStorage;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function nombresSanitizados(): array
    {
        return [
            'acentos y mayusculas' => ['Aceite Primor Ñandú 1L.png', 'aceite-primor-nandu-1l'],
            'espacios en blanco' => ['  Arroz   Pilado  Superior.png', 'arroz-pilado-superior'],
            'guiones repetidos' => ['Gaseosa---Cola---1.5L.png', 'gaseosa-cola-15l'],
            'caracteres especiales' => ['Papel Higiénico ¿Doble? Hoja.png', 'papel-higienico-doble-hoja'],
            'el punto se descarta y no se vuelve guion' => ['Gaseosa 1.5L.png', 'gaseosa-15l'],
            'solo simbolos' => ['***.png', 'producto'],
            'solo guiones' => ['----.png', 'producto'],
            'solo espacios' => ['    .png', 'producto'],
            'nombre vacio' => ['.png', 'producto'],
        ];
    }

    /**
     * El nombre enviado por el cliente nunca se reutiliza tal cual: se sanea
     * para que sea legible y se le anexa un sufijo aleatorio.
     *
     * Nota sobre "1.5L": Str::slug descarta el punto en lugar de convertirlo
     * en guion, de modo que el nombre legible queda "gaseosa-15l". Es cosmetico
     * y no afecta la seguridad, porque el archivo real se llama
     * "gaseosa-15l-<12 alfanumericos>.png" y la extension nunca se inventa.
     * Se deja documentado para que nadie lo lea despues como un error.
     */
    #[DataProvider('nombresSanitizados')]

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_sanea_el_nombre_del_cliente
    public function test_sanea_el_nombre_del_cliente(string $entrada, string $esperado): void
    {
        $ruta = $this->almacenar($entrada);

        $nombre = basename($ruta);
        $prefijo = Str_sinSufijo($nombre);

        $this->assertStringStartsWith($esperado, $prefijo);
        $this->assertNotSame($entrada, $nombre, 'El nombre del cliente no debe conservarse.');
    }

    /**
     * El sufijo aleatorio es lo que impide que dos productos con el mismo
     * nombre de archivo se sobrescriban entre si.
     */

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_dos_subidas_del_mismo_archivo_no_se_pisan
    public function test_dos_subidas_del_mismo_archivo_no_se_pisan(): void
    {
        $primera = $this->almacenar('Aceite Primor.png');
        $segunda = $this->almacenar('Aceite Primor.png');

        $this->assertNotSame($primera, $segunda);
        $this->assertTrue(Storage::disk('public')->exists($primera));
        $this->assertTrue(Storage::disk('public')->exists($segunda));
    }

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_el_archivo_almacenado_cae_en_la_carpeta_de_productos

    public function test_el_archivo_almacenado_cae_en_la_carpeta_de_productos(): void
    {
        $ruta = $this->almacenar('Leche Evaporada.png');

        $this->assertStringStartsWith(ProductImageStorage::DIRECTORIO.'/', $ruta);
        $this->assertStringEndsWith('.png', $ruta);
    }

    /**
     * Un nombre excesivo se trunca antes de anexar el sufijo aleatorio, para
     * no exceder el limite de longitud de archivo del sistema operativo.
     */

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_trunca_los_nombres_excesivamente_largos
    public function test_trunca_los_nombres_excesivamente_largos(): void
    {
        $ruta = $this->almacenar(str_repeat('segmento-de-prueba-', 20).'.png');

        $prefijo = Str_sinSufijo(basename($ruta));

        $this->assertLessThanOrEqual(60, mb_strlen($prefijo));
    }

    /**
     * La extension que decide el almacenado es la real del archivo, no la que
     * declara el cliente. Un PNG subido con nombre "documento.pdf" debe
     * quedar como .png: si se aceptara la extension del cliente, un atacante
     * podria renombrar un archivo arbitrario a .php y servirse como codigo.
     */

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_la_extension_real_manda_sobre_la_declarada
    public function test_la_extension_real_manda_sobre_la_declarada(): void
    {
        $png = $this->archivoReal('real.png', $this->bytesPng());
        $subido = new UploadedFile($png, 'documento.pdf', 'application/pdf', null, true);

        $ruta = $this->servicio->store($subido);

        $this->assertStringEndsWith('.png', $ruta);
        $this->assertFalse(Storage::disk('public')->exists('productos/documento.pdf'));
    }

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_url_devuelve_null_para_entradas_vacias

    public function test_url_devuelve_null_para_entradas_vacias(): void
    {
        $this->assertNull($this->servicio->url(null));
        $this->assertNull($this->servicio->url(''));
        $this->assertNull($this->servicio->url('   '));
    }

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_url_construye_la_direccion_publica

    public function test_url_construye_la_direccion_publica(): void
    {
        $ruta = $this->almacenar('Arroz.png');

        $this->assertSame(Storage::disk('public')->url($ruta), $this->servicio->url($ruta));
    }

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_eliminar_borra_el_archivo_previo

    public function test_eliminar_borra_el_archivo_previo(): void
    {
        $ruta = $this->almacenar('Detergente.png');

        $this->servicio->eliminar($ruta);

        $this->assertFalse(Storage::disk('public')->exists($ruta));
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function rutasHostiles(): array
    {
        return [
            'salto al directorio superior' => ['../../.env'],
            'salto multiple' => ['../../../etc/passwd'],
            'salto combinado con ruta valida' => ['productos/../.env'],
            'ruta absoluta de windows' => ['C:\\Windows\\System32\\config\\SAM'],
        ];
    }

    /**
     * Una ruta con segmentos ".." no debe borrar nada fuera del disco, pero
     * tampoco debe propagar la excepcion de Flysystem. Antes de la correccion
     * este caso devolvia un error 500 al editar el producto.
     */
    #[DataProvider('rutasHostiles')]

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_eliminar_ignora_rutas_con_traversal_sin_lanzar_excepcion
    public function test_eliminar_ignora_rutas_con_traversal_sin_lanzar_excepcion(?string $ruta): void
    {
        $this->servicio->eliminar($ruta);

        $this->assertTrue(true, 'eliminar() no debe lanzar con rutas hostiles.');
    }

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_eliminar_acepta_null_y_cadena_vacia

    public function test_eliminar_acepta_null_y_cadena_vacia(): void
    {
        $this->servicio->eliminar(null);
        $this->servicio->eliminar('');

        $this->assertTrue(true, 'eliminar() es inocuo cuando no hay imagen previa.');
    }

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_eliminar_ignora_un_archivo_inexistente

    public function test_eliminar_ignora_un_archivo_inexistente(): void
    {
        $this->servicio->eliminar('productos/no-existe-este-archivo.png');

        $this->assertFalse(Storage::disk('public')->exists('productos/no-existe-este-archivo.png'));
    }

    /**
     * Las constantes son la traduccion del criterio de aceptacion al codigo.
     * Si alguien las altera, el requisito se rompe en silencio y la suite
     * BDD seguiria en verde porque valida contra la misma constante.
     */

    // php artisan test tests/Unit/ProductImageStorageTest.php --filter=test_las_constantes_reflejan_el_criterio_de_aceptacion
    public function test_las_constantes_reflejan_el_criterio_de_aceptacion(): void
    {
        $this->assertSame(2048, ProductImageStorage::PESO_MAXIMO_KB);
        $this->assertSame(['png', 'jpg', 'jpeg', 'webp'], ProductImageStorage::EXTENSIONES);
        $this->assertSame('public', ProductImageStorage::DISK);
        $this->assertSame('productos', ProductImageStorage::DIRECTORIO);
    }

    private function almacenar(string $nombre): string
    {
        return $this->servicio->store(UploadedFile::fake()->image($nombre, 20, 20));
    }

    private function archivoReal(string $nombre, string $contenido): string
    {
        $ruta = storage_path('framework/testing/'.uniqid('img', true).'-'.$nombre);
        file_put_contents($ruta, $contenido);

        return $ruta;
    }

    private function bytesPng(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
    }
}

if (! function_exists('Str_sinSufijo')) {
    function Str_sinSufijo(string $nombre): string
    {
        return preg_replace('/-[A-Za-z0-9]{12}\.[a-z0-9]+$/', '', $nombre) ?? $nombre;
    }
}
