<?php

namespace App\Http\Requests\Bulk;

use App\Domain\Enums\PriceAdjustmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'type' => [Rule::enum(PriceAdjustmentType::class)],
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
            'type.enum' => 'El campo tipo debe ser price_percentage o price_fixed.',
            'value.required' => 'El campo valor es obligatorio.',
            'value.numeric' => 'El campo valor debe ser un número.',
        ];
    }
}
