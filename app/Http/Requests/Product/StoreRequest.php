<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('api')->can('create', Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $null = 'nullable';

        return [
            'categories' => ['array'],
            'categories.*' => ['exists:categories,id'],
            'code' => [$null],
            'description' => [$null, 'string'],
            'min_stock_warning' => [$null, 'integer'],
            'name' => ['required'],
            'price' => [$null, 'numeric'],
            'stock' => ['required', 'integer'],
            'brand_id' => [$null, 'exists:brands,id'],
            'storage_location_id' => [$null, 'exists:storage_locations,id'],
            'supplier_id' => [$null, 'exists:suppliers,id'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'categories.array' => 'Las categorías deben ser un arreglo.',
            'categories.*.exists' => 'La categoría seleccionada no existe.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'min_stock_warning.integer' => 'La advertencia de stock mínimo debe ser un número entero.',
            'name.required' => 'El nombre es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'brand_id.exists' => 'La marca seleccionada no existe.',
            'storage_location_id.exists' => 'La ubicación de almacenamiento seleccionada no existe.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',
        ];
    }
}
