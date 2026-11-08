<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreQuoteRequest extends FormRequest
{
    /**
     * RN-01: cotizar no exige RUC ni cuenta. Un visitante (sin usuario) y una
     * Persona Natural pueden solicitar; el Empresario administra su catalogo,
     * no genera solicitudes con su propio rol.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user === null || $user->esPersonaNatural();
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
     * Reglas dinamicas de HU-04 y RN-05:
     *  - el producto debe seguir publicado;
     *  - debe tener stock;
     *  - la cantidad debe alcanzar el MOQ;
     *  - la cantidad no puede exceder el stock disponible.
     *
     * La validacion vive en el servidor: ocultar el boton en la interfaz no
     * impide que un cliente manipule el formulario.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['producto_id', 'cantidad_solicitada'])) {
                return;
            }

            $producto = Product::find($this->input('producto_id'));
            $cantidad = (int) $this->input('cantidad_solicitada');

            if ($producto === null) {
                return;
            }

            if (! $producto->is_active) {
                $validator->errors()->add(
                    'producto_id',
                    'El producto ya no esta publicado en el catalogo'
                );

                return;
            }

            if ((int) $producto->stock_disponible <= 0) {
                $validator->errors()->add(
                    'producto_id',
                    'El producto se encuentra temporalmente sin stock'
                );

                return;
            }

            if ((int) $producto->stock_disponible < (int) $producto->moq_cantidad_minima) {
                $validator->errors()->add(
                    'cantidad_solicitada',
                    "El stock disponible ({$producto->stock_disponible} bultos) no alcanza el pedido minimo de {$producto->moq_cantidad_minima} bultos"
                );

                return;
            }

            if ($cantidad < $producto->moq_cantidad_minima) {
                $validator->errors()->add(
                    'cantidad_solicitada',
                    "La cantidad minima exigida por este distribuidor es de {$producto->moq_cantidad_minima} bultos"
                );

                return;
            }

            if ($cantidad > $producto->stock_disponible) {
                $validator->errors()->add(
                    'cantidad_solicitada',
                    "Solo hay {$producto->stock_disponible} bultos disponibles para este producto"
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
