<?php

namespace App\Http\Requests\Checkout;

use App\Services\ShippingService;
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
     * Authenticated customers check out against a saved `address_id`. Guests (no Sanctum
     * user — identified only by their `X-Cart-Token` cart) submit shipping details inline
     * instead, since they have no saved Address to reference.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user('sanctum');
        $isGuest = ! $user;

        return [
            'address_id' => [
                Rule::requiredIf(! $isGuest),
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')->where('user_id', $user?->id),
            ],
            'recipient_name' => [Rule::requiredIf($isGuest), 'nullable', 'string', 'max:255'],
            'recipient_phone' => [Rule::requiredIf($isGuest), 'nullable', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'shipping_email' => ['nullable', 'email', 'max:255'],
            'shipping_address_line1' => [Rule::requiredIf($isGuest), 'nullable', 'string', 'max:255'],
            'shipping_address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => [Rule::requiredIf($isGuest), 'nullable', 'string', Rule::in(ShippingService::validCities())],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', 'string', 'in:cod'],
            'coupon_code' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
