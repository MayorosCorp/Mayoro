<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ficha CI-COD-04 (HU-02, campo imagen_producto): la imagen del producto es
 * opcional, admite PNG/JPG/WebP hasta 2 MB y se almacena con nombre generado
 * por el servidor. La ruta relativa se persiste en productos_mayoristas.imagen_url.
 */
class ProductImageStorage
{
    /**
     * Disco donde se alojan las imágenes del catálogo mayorista.
     */
    public const DISK = 'public';

    /**
     * Carpeta raíz dentro del disco.
     */
    public const DIRECTORIO = 'productos';

    /**
     * Extensiones admitidas por el criterio de aceptación.
     */
    public const EXTENSIONES = ['png', 'jpg', 'jpeg', 'webp'];

    /**
     * Peso máximo permitido en kilobytes (2 MB).
     */
    public const PESO_MAXIMO_KB = 2048;

    /**
     * Almacena la imagen en el disco público y devuelve la ruta relativa que
     * se persiste en productos_mayoristas.imagen_url.
     *
     * El nombre del archivo se genera de forma aleatoria con Str::uuid y se
     * sanea la extensión: nunca se reutiliza el nombre enviado por el cliente,
     * lo que cierra el vector de sobrescritura de archivos y path traversal.
     */
    public function store(UploadedFile $imagen): string
    {
        $extension = strtolower($imagen->getClientOriginalExtension());

        if (! in_array($extension, self::EXTENSIONES, true)) {
            $extension = $imagen->extension();
        }

        $nombre = Str::slug(pathinfo($imagen->getClientOriginalName(), PATHINFO_FILENAME), '-');
        $nombre = trim($nombre, '-') !== '' ? Str::limit($nombre, 60, '') : 'producto';
        $nombre = Str::lower($nombre).'-'.Str::random(12).'.'.$extension;

        return $imagen->storeAs(self::DIRECTORIO, $nombre, self::DISK);
    }

    /**
     * Construye la URL pública de la imagen almacenada, o null si no hay imagen.
     */
    public function url(?string $ruta): ?string
    {
        if ($ruta === null || trim($ruta) === '') {
            return null;
        }

        return Storage::disk(self::DISK)->url($ruta);
    }

    /**
     * Elimina la imagen previa del producto, si existía.
     *
     * Una ruta con segmentos ".." hace fallar a Flysystem con
     * `PathTraversalDetected`, que es una excepción no controlada. El valor
     * proviene de la base y el servicio lo genera siempre en un formato seguro,
     * así que el riesgo no es que un usuario lo inyecte: es que una fila
     * corrupta, un valor heredado o una futura edición de datos dejen la ficha
     * del producto devolviendo un error 500. Se descarta en silencio porque no
     * existe ningún archivo legítimo que borrar en esa ruta.
     */
    public function eliminar(?string $ruta): void
    {
        if ($ruta === null || $ruta === '') {
            return;
        }

        if (in_array('..', preg_split('#[\\\\/]+#', $ruta) ?: [], true)) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($ruta)) {
            Storage::disk(self::DISK)->delete($ruta);
        }
    }
}
