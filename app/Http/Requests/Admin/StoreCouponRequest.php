<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:255',
                'unique:coupons,code',
                Rule::requiredIf(fn (): bool => $this->input('method', 'code') !== 'automatic'),
            ],
            'method' => ['sometimes', 'string', 'in:code,automatic'],
            'discount_type' => ['required', 'string', 'in:percent,flat,free_delivery'],
            'discount_value' => ['nullable', 'integer', 'min:1', 'required_unless:discount_type,free_delivery'],
            'max_discount' => ['nullable', 'integer', 'min:1'],
            'min_order_value' => ['sometimes', 'integer', 'min:0'],
            'first_order_only' => ['sometimes', 'boolean'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'max_uses_per_user' => ['nullable', 'integer', 'min:1'],
            'allow_combination' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'brand_ids' => ['sometimes', 'array'],
            'brand_ids.*' => ['integer', 'distinct', 'exists:brands,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'distinct', 'exists:tags,id'],
        ];
    }
}
