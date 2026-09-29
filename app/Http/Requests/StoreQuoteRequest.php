<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreQuoteRequest extends FormRequest
{
    /**
     * Ficha CI-COD-09: solo el rol bodega puede solicitar cotizaciones.
     */
    public function authorize(): bool
    {
        return $this->user()?->rol === 'bodega';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'exists:productos_mayoristas,id'],
            'cantidad_solicitada' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Regla de negocio dinamica HU-04.CA-01: la cantidad debe igualar o superar
     * el MOQ definido por el distribuidor para ese producto.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['producto_id', 'cantidad_solicitada'])) {
                return;
            }

            $producto = Product::find($this->input('producto_id'));
            $cantidad = (int) $this->input('cantidad_solicitada');

            if ($producto !== null && $cantidad < $producto->moq_cantidad_minima) {
                $validator->errors()->add(
                    'cantidad_solicitada',
                    "La cantidad minima exigida por este distribuidor es de {$producto->moq_cantidad_minima} bultos"
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'producto_id.required' => 'Debe seleccionar un producto del catalogo',
            'producto_id.exists' => 'El producto seleccionado no existe en el catalogo',
            'cantidad_solicitada.required' => 'Debe ingresar la cantidad de bultos a cotizar',
            'cantidad_solicitada.min' => 'Debe ingresar un valor numerico positivo mayor a cero',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'producto_id' => 'producto',
            'cantidad_solicitada' => 'cantidad',
        ];
    }
}
