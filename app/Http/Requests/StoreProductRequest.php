<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            // RN-04: los tramos son opcionales. El formulario puede enviar una
            // fila vacia para que el distribuidor empiece a escribir; se
            // descarta antes de validar.
            'tramos' => ['nullable', 'array', 'max:10'],
            'tramos.*.cantidad_minima' => ['required', 'integer', 'min:1'],
            'tramos.*.precio_bulto' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        ];
    }

    /**
     * Normaliza los tramos antes de validar: descarta filas vacias y los ordena
     * por umbral ascendente para que el distribuidor no tenga que escribirlos
     * en un orden concreto. La exclusividad y el descuento se comprueban
     * despues, sobre la lista ya ordenada.
     */
    protected function prepareForValidation(): void
    {
        $tramos = collect($this->input('tramos', []))
            ->filter(fn ($tramo): bool => is_array($tramo) && (
                filled($tramo['cantidad_minima'] ?? null)
                || filled($tramo['precio_bulto'] ?? null)
            ))
            ->sortBy(fn (array $tramo): int => (int) ($tramo['cantidad_minima'] ?? 0))
            ->values()
            ->all();

        $this->merge(['tramos' => $tramos]);
    }

    /**
     * RN-05: un producto solo puede venderse por bultos cuando el stock cubre
     * el pedido mínimo. Se admite 0 (agotado deliberado) o una cifra >= MOQ;
     * un valor intermedio promete una compra que el propio catálogo bloquea.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['stock_disponible', 'moq_cantidad_minima'])) {
                return;
            }

            $stock = (int) $this->input('stock_disponible');
            $moq = (int) $this->input('moq_cantidad_minima');

            if ($stock > 0 && $stock < $moq) {
                $validator->errors()->add(
                    'stock_disponible',
                    "El stock debe ser 0 o al menos el pedido minimo de {$moq} bultos"
                );
            }

            $this->validarTramos($validator);
        });
    }

    /**
     * RN-04: un tramo solo tiene sentido si empieza por encima del MOQ (en el
     * MOQ rige el precio base), si mejora el precio base y si cada umbral
     * mayor ofrece un precio menor. Los umbrales repetidos quedan prohibidos
     * por la clave unica de la tabla.
     */
    private function validarTramos(Validator $validator): void
    {
        $tramos = $this->input('tramos', []);

        if (! is_array($tramos) || $tramos === []) {
            return;
        }

        if ($validator->errors()->hasAny(['precio_bulto', 'moq_cantidad_minima'])) {
            return;
        }

        $errorEnTramo = collect($validator->errors()->keys())
            ->contains(fn (string $clave): bool => str_starts_with($clave, 'tramos'));

        if ($errorEnTramo) {
            return;
        }

        $moq = (int) $this->input('moq_cantidad_minima');
        $precioBase = (float) $this->input('precio_bulto');
        $anterior = null;

        foreach ($tramos as $indice => $tramo) {
            $cantidad = (int) ($tramo['cantidad_minima'] ?? 0);
            $precio = (float) ($tramo['precio_bulto'] ?? 0);

            if ($cantidad <= $moq) {
                $validator->errors()->add(
                    "tramos.{$indice}.cantidad_minima",
                    "Cada tramo debe empezar por encima del pedido minimo de {$moq} bultos"
                );
            }

            if ($precio >= $precioBase) {
                $validator->errors()->add(
                    "tramos.{$indice}.precio_bulto",
                    'El precio del tramo debe ser menor al precio base por bulto'
                );
            }

            if ($anterior !== null) {
                if ($cantidad === $anterior['cantidad_minima']) {
                    $validator->errors()->add(
                        "tramos.{$indice}.cantidad_minima",
                        'No puede haber dos tramos con la misma cantidad minima'
                    );
                } elseif ($precio >= $anterior['precio_bulto']) {
                    $validator->errors()->add(
                        "tramos.{$indice}.precio_bulto",
                        'Cada tramo debe ofrecer un precio menor que el tramo anterior'
                    );
                }
            }

            $anterior = [
                'cantidad_minima' => $cantidad,
                'precio_bulto' => $precio,
            ];
        }
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
            'tramos.*.cantidad_minima.required' => 'Completa la cantidad mínima del tramo o elimina la fila.',
            'tramos.*.cantidad_minima.integer' => 'La cantidad mínima del tramo debe ser un número entero de bultos.',
            'tramos.*.cantidad_minima.min' => 'La cantidad mínima del tramo debe ser mayor a cero.',
            'tramos.*.precio_bulto.required' => 'Completa el precio del tramo o elimina la fila.',
            'tramos.*.precio_bulto.numeric' => 'El precio del tramo debe ser un valor numérico.',
            'tramos.*.precio_bulto.gt' => 'El precio del tramo debe ser mayor a cero.',
            'tramos.*.precio_bulto.decimal' => 'El precio del tramo no debe superar las dos cifras decimales.',
        ];
    }
}
