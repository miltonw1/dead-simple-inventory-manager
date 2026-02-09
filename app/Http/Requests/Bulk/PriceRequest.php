<?php

namespace App\Http\Requests\Bulk;

use Illuminate\Foundation\Http\FormRequest;

class PriceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:price_percentage,price_fixed'],
            'value' => ['required', 'numeric'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'El campo tipo es obligatorio.',
            'type.string' => 'El campo tipo debe ser una cadena.',
            'type.in' => 'El campo tipo debe ser price_percentage o price_fixed.',
            'value.required' => 'El campo valor es obligatorio.',
            'value.numeric' => 'El campo valor debe ser un número.',
        ];
    }
}
