<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
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
            'address_id' => [
                'required',
                'integer',
                Rule::exists('addresses', 'id')->where('user_id', $this->user()?->id),
            ],
            'payment_method' => ['required', 'string', 'in:cod'],
            'coupon_code' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
