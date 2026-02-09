<?php

namespace App\Http\Requests\Bulk;

use Illuminate\Foundation\Http\FormRequest;

class StockRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:purchase,sale,adjustment,return'],
            'changes' => ['required', 'array'],
            'changes.*.id' => ['required', 'integer', 'exists:products,id'],
            'changes.*.value' => ['required', 'integer', 'min:0'],
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
            'type.in' => 'El campo tipo debe ser purchase, sale, adjustment o return.',
            'changes.required' => 'El campo cambios es obligatorio.',
            'changes.array' => 'El campo cambios debe ser un arreglo.',
            'changes.*.id.required' => 'El campo id es obligatorio.',
            'changes.*.id.integer' => 'El campo id debe ser un número entero.',
            'changes.*.id.exists' => 'El producto especificado no existe.',
            'changes.*.value.required' => 'El campo valor es obligatorio.',
            'changes.*.value.integer' => 'El campo valor debe ser un número entero.',
            'changes.*.value.min' => 'El campo valor debe ser al menos 0.',
        ];
    }
}
