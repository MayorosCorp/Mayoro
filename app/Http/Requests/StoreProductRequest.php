<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // El middleware RBAC CheckRole ya protege la ruta
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:100'],
            'presentacion' => ['required', 'string', 'max:100'],
            'unidades_por_bulto' => ['required', 'integer', 'min:1'],
            // decimal:0,2 reserva el maximo de dos cifras decimales exigido por
            // la convencion decimal(10,2); la columna redondearia un tercer
            // decimal y provocaria un desfase con precio_unitario_sugerido.
            'precio_bulto' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'moq_cantidad_minima' => ['required', 'integer', 'min:1'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            // El alta exige la cifra de forma explicita: el default 0 de la
            // columna hacia que todo producto naciera "Temporalmente sin
            // stock" sin que el distribuidor hubiera podido evitarlo. El 0
            // sigue siendo valido, pero tiene que ser una decision.
            'stock_disponible' => ['required', 'integer', 'min:0', 'max:1000000'],
            'imagen' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    /**
     * Custom validation messages required by QA HU-02 specification.
     */
    public function messages(): array
    {
        return [
            'precio_bulto.gt' => 'Debe ingresar un valor numérico positivo mayor a cero',
            'precio_bulto.numeric' => 'Debe ingresar un valor numérico positivo mayor a cero',
            'precio_bulto.min' => 'Debe ingresar un valor numérico positivo mayor a cero',
            'precio_bulto.decimal' => 'El precio no debe superar las dos cifras decimales',
            'moq_cantidad_minima.min' => 'Debe ingresar un valor numérico positivo mayor a cero',
            'moq_cantidad_minima.integer' => 'Debe ingresar un valor numérico positivo mayor a cero',
            'unidades_por_bulto.min' => 'Debe ingresar un valor numérico positivo mayor a cero',
            'unidades_por_bulto.integer' => 'Debe ingresar un valor numérico positivo mayor a cero',
            'stock_disponible.required' => 'Debe indicar cuántas unidades tiene disponibles',
            'stock_disponible.integer' => 'La cantidad de stock debe ser un número entero',
            'stock_disponible.min' => 'La cantidad de stock no puede ser negativa',
            'imagen.image' => 'La imagen del producto debe ser un archivo PNG, JPG o WebP',
            'imagen.mimes' => 'La imagen del producto solo admite formato PNG, JPG o WebP',
            'imagen.max' => 'La imagen del producto no debe superar los 2 MB',
        ];
    }
}
