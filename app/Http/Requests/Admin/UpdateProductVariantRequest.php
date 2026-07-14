<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => ['sometimes', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($this->route('variant'))],
            'size_label' => ['sometimes', 'string', 'max:255'],
            'price_override' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
