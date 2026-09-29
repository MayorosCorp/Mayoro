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
            'precio_bulto' => ['required', 'numeric', 'gt:0'],
            'moq_cantidad_minima' => ['required', 'integer', 'min:1'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'imagen_url' => ['nullable', 'string', 'max:500'],
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
            'moq_cantidad_minima.min' => 'Debe ingresar un valor numérico positivo mayor a cero',
            'moq_cantidad_minima.integer' => 'Debe ingresar un valor numérico positivo mayor a cero',
            'unidades_por_bulto.min' => 'Debe ingresar un valor numérico positivo mayor a cero',
            'unidades_por_bulto.integer' => 'Debe ingresar un valor numérico positivo mayor a cero',
        ];
    }
}
