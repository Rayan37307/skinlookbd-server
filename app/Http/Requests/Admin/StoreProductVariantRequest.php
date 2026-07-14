<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantRequest extends FormRequest
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
            'sku' => ['required', 'string', 'max:255', 'unique:product_variants,sku'],
            'size_label' => ['required', 'string', 'max:255'],
            'price_override' => ['nullable', 'integer', 'min:0'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
