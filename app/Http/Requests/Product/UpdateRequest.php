<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $this->user('api')->can('update', $product);
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
            'stock' => ['required', 'integer', 'min:0'],
            'brand_id' => [$null, 'exists:brands,id'],
            'storage_location_id' => [$null, 'exists:storage_locations,id'],
            'supplier_id' => [$null, 'exists:suppliers,id'],
        ];
    }
}
