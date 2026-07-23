<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($this->route('product'))],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string'],
            'ingredients' => ['nullable', 'string'],
            'additional_information' => ['sometimes', 'array'],
            'additional_information.*.label' => ['required_with:additional_information', 'string', 'max:255'],
            'additional_information.*.value' => ['required_with:additional_information', 'string', 'max:255'],
            'base_price' => ['sometimes', 'integer', 'min:0'],
            'sale_price' => ['nullable', 'integer', 'min:0', function ($attribute, $value, $fail) {
                $basePrice = $this->input('base_price', $this->route('product')?->base_price);

                if ($value !== null && $basePrice !== null && $value >= $basePrice) {
                    $fail('The sale price must be less than the regular price.');
                }
            }],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', 'in:draft,active,archived'],
            'track_inventory' => ['sometimes', 'boolean'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'free_shipping' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'focus_keyword' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'string', 'max:255', 'url'],
            'skin_type_ids' => ['sometimes', 'array'],
            'skin_type_ids.*' => ['integer', 'exists:skin_types,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['integer', 'exists:labels,id'],
            'related_product_ids' => ['sometimes', 'array'],
            'related_product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }
}
